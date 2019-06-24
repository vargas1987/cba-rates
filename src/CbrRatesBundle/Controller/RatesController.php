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
use Symfony\Component\Routing\Annotation\Route;

class RatesController extends AbstractController
{
    /**
     * @Route("/", name="rates-list")
     */
    public function index()
    {
        $qb = $this->getEm()->getRepository('CbrRatesBundle:BillingCurrencyRate')
            ->createQueryBuilder('bcr')
//            ->andWhere('bcr.currencyFrom = :currencyFrom')
//            ->andWhere('bcr.currencyTo = :currencyTo')
//            ->setParameter('currencyFrom', 'RUB')
//            ->setParameter('currencyTo', 'USD')
            ->addOrderBy('bcr.id')
        ;
        try {
            /** @var Pagerfanta|BillingCurrencyRate[] $pager */
            $pager = $this->get(PagerService::class)->getPagerByQueryBuilder($qb, [
                PagerService::OPT_PAGE => 1,
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
