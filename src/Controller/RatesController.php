<?php

namespace CbrRates\Controller;

use CbrRates\Entity\BillingCurrency;
use CbrRates\Entity\BillingCurrencyRate;
use CbrRates\Exception\BasicException;
use CbrRates\Form\CurrencyFilterForm;
use CbrRates\Repository\BillingCurrencyRateRepository;
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
        $qb = $currencyRateRepo->getRatesQb(['currencyTo' => BillingCurrency::CODE_RUB]);

        if ($form->isSubmitted()&& $form->isValid()) {
            $qb = $currencyRateRepo->getRatesQb([
                'currencyFrom' => $form->get('currency')->getData(),
                'currencyTo' => BillingCurrency::CODE_RUB,
                'rateUpperDate' => $form->get('rateUpperDate')->getData(),
                'rateLowerDate' => $form->get('rateLowerDate')->getData(),
                'currencySort' => $form->get('currencySort')->getData(),
                'dateSort' => $form->get('rateDateSort')->getData(),
            ]);
        }

        try {
            /** @var Pagerfanta|BillingCurrencyRate[] $pager */
            $pager = $this->get(PagerService::class)->getPagerByQueryBuilder($qb, [
                PagerService::OPT_PAGE => $page,
                PagerService::OPT_PER_PAGE => 50,
                PagerService::OPT_PER_PAGE_LIMIT => 50,
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
     * @return ObjectManager|EntityManager|object
     */
    protected function getEm()
    {
        return $this->getDoctrine()->getManager();
    }
}
