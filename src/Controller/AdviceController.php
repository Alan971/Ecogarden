<?php

namespace App\Controller;

use App\Repository\AdviceRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AdviceController extends AbstractController
{
    #[Route('api/conseil', name: 'app_advice', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function advices(AdviceRepository $adviceRepository): JsonResponse
    {
        $advices = $adviceRepository->findAllInMonth(date('m'));
        return $this->json($advices);
    }

    #[Route('api/conseil/{month}', name: 'app_month_advice', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function selectedMonthAdvices(int $month, AdviceRepository $adviceRepository): JsonResponse
    {
        if($month < 1 || $month > 12){
            return new JsonResponse(['message' => 'Le mois doit être compris entre 1 et 12'], 400);
        }
        $advices = $adviceRepository->findAllInMonth($month);
        return $this->json($advices);
    }

    #[Route('api/conseil', name: 'app_month_advice', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function AddAdvices( AdviceRepository $adviceRepository): JsonResponse
    {
        $month = date('m');
//TO DO

    }

}
