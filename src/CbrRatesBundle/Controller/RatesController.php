<?php

namespace CbrRatesBundle\Controller;

use CbrRatesBundle\Entity\BillingCurrency;
use CbrRatesBundle\Entity\BillingCurrencyRate;
use CbrRatesBundle\Exception\BasicException;
use CbrRatesBundle\Service\PagerService;
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
        $qb = $this->getEm()->getRepository('CbrRatesBundle:BillingCurrencyRate')
            ->getRatesQb()
        ;

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
            'controller_name' => 'RatesController',
            'pager' => $pager->getCurrentPageResults(),
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
