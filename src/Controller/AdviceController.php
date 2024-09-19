<?php

namespace App\Controller;

use App\Entity\Advice;
use Doctrine\ORM\Mapping\Entity;
use App\Repository\AdviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AdviceController extends AbstractController
{

    // #[Route('api/advices', name: 'app_advice', methods: ['GET'])]
    // public function advices(AdviceRepository $adviceRepository): JsonResponse
    // {
    //     $advices = $adviceRepository->findAllBSortedByMonth();

    //     return $this->json($advices);
    
    // }
}
