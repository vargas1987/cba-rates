<?php

namespace CbrRates\Controller;

use CbrRates\Entity\BillingCurrency;
use CbrRates\Entity\BillingCurrencyRate;
use CbrRates\Exception\BasicException;
use CbrRates\Form\ChartForm;
use CbrRates\Form\CurrencyFilterForm;
use CbrRates\Repository\BillingCurrencyRateRepository;
use CbrRates\Repository\BillingCurrencyRepository;
use CbrRates\Service\PagerService;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        try {
            /** @var Pagerfanta|BillingCurrencyRate[] $pager */
            $pager = $this->get(PagerService::class)->getPagerByQueryBuilder($qb, [
                PagerService::OPT_PAGE => $page,
                PagerService::OPT_PER_PAGE => 30,
                PagerService::OPT_PER_PAGE_LIMIT => 30,
            ]);
        } catch (BasicException $exception) {
            return $this->redirectToRoute('backend-dashboard');
        }

        return $this->render('rates/index.html.twig', [
            'form'   => $form->createView(),
            'controller_name' => 'RatesController',
            'pager' => $pager,
        ]);
    }

    /**
     * @Route("/statistics", name="rates-statistics")
     * @param Request $request
     *
     * @return Response
     */
    public function statisticsAction(Request $request)
    {
        /** @var BillingCurrency[] $currencies */
        $currencies = $this->getEm()->getRepository('CbrRates:BillingCurrency')->findAll();
        $form = $this->createForm(ChartForm::class);

        return $this->render('rates/statistics.html.twig', [
            'form'   => $form->createView(),
            'controller_name' => 'RatesController',
            'currencies' => $currencies,
        ]);
    }

    /**
     * @return ObjectManager|EntityManager|object
     */
    protected function getEm()
    {
        return $this->getDoctrine()->getManager();
    }
}