<?php

namespace App\Controller;

use App\Entity\ExchangeRate;
use App\Form\ExchangeRateType;
use App\Repository\ExchangeRateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/exchange-rate")
 */
class ExchangeRateController extends AbstractController
{
    /**
     * @Route("/", name="exchange_rate_index", methods={"GET"})
     */
    public function index(ExchangeRateRepository $exchangeRateRepository): Response
    {
        return $this->render('exchange_rate/index.html.twig', [
            'exchange_rates' => $exchangeRateRepository->findAll(),
            'SCALE' => \App\Service\CurrencyConverter::SCALE,
        ]);
    }

    /**
     * @Route("/new", name="exchange_rate_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $exchangeRate = new ExchangeRate();
        $form = $this->createForm(ExchangeRateType::class, $exchangeRate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($exchangeRate);
            $entityManager->flush();

            return $this->redirectToRoute('exchange_rate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('exchange_rate/new.html.twig', [
            'exchange_rate' => $exchangeRate,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="exchange_rate_show", methods={"GET"})
     */
    public function show(ExchangeRate $exchangeRate): Response
    {
        return $this->render('exchange_rate/show.html.twig', [
            'exchange_rate' => $exchangeRate,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="exchange_rate_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, ExchangeRate $exchangeRate): Response
    {
        $form = $this->createForm(ExchangeRateType::class, $exchangeRate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('exchange_rate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('exchange_rate/edit.html.twig', [
            'exchange_rate' => $exchangeRate,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="exchange_rate_delete", methods={"POST"})
     */
    public function delete(Request $request, ExchangeRate $exchangeRate): Response
    {
        if ($this->isCsrfTokenValid('delete'.$exchangeRate->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($exchangeRate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('exchange_rate_index', [], Response::HTTP_SEE_OTHER);
    }
}
