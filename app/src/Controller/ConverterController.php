<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\ConverterType;
use App\Service\CurrencyConverter;
use Symfony\Component\HttpFoundation\JsonResponse;

class ConverterController extends AbstractController
{
    /**
     * @Route("/converter", name="converter")
     */
    public function index(): Response
    {
        $form = $this->createForm(ConverterType::class);
        return $this->render('converter/index.html.twig', [
            'controller_name' => 'ConverterController',
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/converter/result", name="converter_result", methods={"GET"})
     */
    public function result(Request $request): Response
    {
        if ($request->isXMLHttpRequest()) {
            $from = $request->query->get('from');
            $to = $request->query->get('to');
            $amount = $request->query->get('amount');
            // TODO валидатор
            $result = CurrencyConverter::estimation($from, $to, $amount);
            return new JsonResponse($result);
        }
    }
}
