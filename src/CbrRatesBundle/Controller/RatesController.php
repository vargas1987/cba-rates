<?php

namespace CbrRatesBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rates")
 */
class RatesController extends AbstractController
{
    /**
     * @Route("/", name="rates-list")
     * @Template
     * @param Request $request
     * @return array|Response
     */
    public function indexAction(Request $request)
    {
        return [];
    }
}
