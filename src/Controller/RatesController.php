<?php

namespace CbrRates\Controller;

use CbrRates\Entity\BillingCurrency;
use CbrRates\Entity\BillingCurrencyRate;
use CbrRates\Exception\BasicException;
use CbrRates\Exception\FileDownloadException;
use CbrRates\Form\ChartForm;
use CbrRates\Form\CurrencyFilterForm;
use CbrRates\Repository\BillingCurrencyRateRepository;
use CbrRates\Repository\BillingCurrencyRepository;
use CbrRates\Service\PagerService;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use function Symfony\Component\DependencyInjection\Tests\Fixtures\factoryFunction;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RatesController extends AbstractController
{
    /**
     * @Route("/{page}", name="rates-list", defaults={"page" = 1}, requirements={"page" = "\d+"})
     * @param Request $request
     * @return Response
     */
    public function index(Request $request, $page)
    {
        $form = $this->createForm(CurrencyFilterForm::class);

        $form->handleRequest($request);
        /** @var BillingCurrencyRateRepository $currencyRateRepo */
        $currencyRateRepo = $this->getEm()->getRepository('CbrRates:BillingCurrencyRate');
        $params = [
            'currencyTo' => BillingCurrency::CODE_RUB,
            'rateUpperDate' => new \DateTime(),
            'currencyFrom' => $form->get('currency')->getData(),
        ];

        if ($form->isSubmitted()&& $form->isValid()) {
            $params = [
                'currencyFrom' => $form->get('currency')->getData(),
                'currencyTo' => BillingCurrency::CODE_RUB,
                'rateUpperDate' => $form->get('rateUpperDate')->getData(),
                'rateLowerDate' => $form->get('rateLowerDate')->getData(),
                'currencySort' => $form->get('currencySort')->getData(),
                'dateSort' => $form->get('rateDateSort')->getData(),
            ];
        }

        $qb = $currencyRateRepo->getRatesQb($params);

        $dataProvider = $this->prepareDataprovider([
            'rateUpperDate' => $form->get('rateUpperDate')->getData(),
            'rateLowerDate' => $form->get('rateLowerDate')->getData(),
            'currencyTo' => BillingCurrency::CODE_RUB,
        ]);

        try {
            /** @var Pagerfanta|BillingCurrencyRate[] $pager */
            $pager = $this->get(PagerService::class)->getPagerByQueryBuilder($qb, [
                PagerService::OPT_PAGE => $page,
                PagerService::OPT_PER_PAGE => 30,
                PagerService::OPT_PER_PAGE_LIMIT => 30,
            ]);

            if ($request->request->get('download')) {
                return $this->downloadJson($dataProvider);
            }
        } catch (BasicException $exception) {
            return $this->redirectToRoute('backend-dashboard');
        }

        $graphs = $this->prepareGraphs($dataProvider);

        return $this->render('rates/index.html.twig', [
            'form'   => $form->createView(),
            'controller_name' => 'RatesController',
            'pager' => $pager,
            'graphs' => $graphs,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * @Route("/statistics", name="rates-statistics")
     * @param Request $request
     *
     * @return Response
     */
    public function statistics(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $form = $this->createForm(CurrencyFilterForm::class);
            $params = $request->request->get($form->getName());
            $dataProvider = $this->prepareDataprovider([
                'rateUpperDate' => $params['rateUpperDate'] ?: null,
                'rateLowerDate' => $params['rateLowerDate'] ?: null,
                'currencyTo' => BillingCurrency::CODE_RUB,
            ]);

            $graphs = $this->prepareGraphs($dataProvider);

            return new JsonResponse([
                'graphs' => $graphs,
                'dataProvider' => $dataProvider,
            ]);
        }

        return new JsonResponse([]);
    }

    /**
     * @param array $dataProvider
     * @return BinaryFileResponse
     * @throws BasicException
     */
    public function downloadJson(array $dataProvider)
    {
        try {
            $fileName = uniqid('json_').'.json';
            $filePath = $this->get('kernel')->getProjectDir().'/var/downloads/'.$fileName;
            $fs = new Filesystem();
            $fs->dumpFile($filePath, (string) json_encode($dataProvider));

            return $this->file($filePath);
        } catch (\Exception $e) {
            $this->get(Logger::class)->error($e->getMessage(), ['exception' => $e]);

            throw new BasicException('Упс! Вы сломали Валюту');
        }
    }

    /**
     * @param array $dataProvider
     * @return array
     */
    protected function prepareGraphs(array $dataProvider) :array
    {
        $usedCurrencies = [];

        foreach ($dataProvider as $data) {
            $usedCurrencies = array_merge($usedCurrencies, array_keys($data));
        }

        $usedCurrencies = array_unique($usedCurrencies);

        $graphs = $this->getEm()
            ->getRepository('CbrRates:BillingCurrency')
            ->findBy([], ['charCode' => 'ASC']);

        $graphs = array_reduce($graphs, function ($result, BillingCurrency $currency) {
            $result[$currency->getCharCode()] = [
                'charCode' => $currency->getCharCode(),
                'numCode' => $currency->getNumCode(),
                'name' => $currency->getName(),
            ];

            return $result;
        }, []);

        return array_filter($graphs, function ($graph) use ($usedCurrencies) {
            return \in_array($graph['charCode'], $usedCurrencies);
        });
    }

    /**
     * @param array $params
     * @return array|null
     */
    protected function prepareDataprovider(array $params) :?array
    {
        /** @var BillingCurrencyRateRepository $currencyRateRepo */
        $currencyRateRepo = $this->getEm()->getRepository('CbrRates:BillingCurrencyRate');
        $dataProvider = $currencyRateRepo
            ->getGraphData($params);

        return array_reduce($dataProvider, function ($result, $data) {
            $currencies = json_decode($data['currencies'], true);

            $valeus = json_decode($data['values'], true);
            $currencies = array_combine($currencies, $valeus);
            $currencies = array_merge([
                'date' => $data['date']->format('Y-m-d')
            ], $currencies);

            $result[] = $currencies;

            return $result;
        }, []);
    }

    /**
     * @return ObjectManager|EntityManager|object
     */
    protected function getEm()
    {
        return $this->getDoctrine()->getManager();
    }
}
