<?php

namespace CbrRatesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class RatesController extends AbstractController
{
    /**
     * @Route("/", name="rates-list")
     */
    public function index()
    {
        return $this->render('rates/index.html.twig', [
            'controller_name' => 'RatesController',
        ]);
    }
}
