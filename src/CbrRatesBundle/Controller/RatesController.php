<?php

namespace CbrRatesBundle\Controller;

use CbrRatesBundle\Entity\BillingCurrency;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class RatesController extends AbstractController
{
    /**
     * @Route("/", name="rates-list")
     */
    public function index()
    {
        $count = $this->getEm()->getRepository(BillingCurrency::class)->count([]);

        return $this->render('rates/index.html.twig', [
            'controller_name' => 'RatesController',
            'count' => $count,
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
