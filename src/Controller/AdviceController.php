<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AdviceController extends AbstractController
{
    /**
     *  affiche les conseils du mois en cours à l'utilisateur
     *
     * @return JsonResponse
    **/
    #[Route('api/conseil', name: 'app_advice', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function advices(AdviceRepository $adviceRepository): JsonResponse
    {
        $advices = $adviceRepository->findAllInMonth(date('m'));
        return $this->json($advices);

    }
    /**
     *  affiche les conseils du mois demandé à l'utilisateur
     *
     * @param int $month
     * @return JsonResponse
     *
    **/
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('api/conseil/{month}', name: 'app_month_advice', methods: ['GET'])]
    public function selectedMonthAdvices(int $month, AdviceRepository $adviceRepository, TagAwareCacheInterface $cachePool): JsonResponse
    {
        if($month < 1 || $month > 12){
            return new JsonResponse(['message' => 'Le mois doit être compris entre 1 et 12'], 400);
        }
        $idCache = 'advices_'.$month;
        $advices = $cachePool->get($idCache, function(ItemInterface $item) use ($adviceRepository, $month)
        {
            $item->tag('advices');
            $advices = $adviceRepository->findAllInMonth($month);
            $item->expiresAfter(3600); //le cache dure 1 heure. il expire au bout du même tempe que le JWT
            return $advices;
        }
        ); 

        //$advices = $adviceRepository->findAllInMonth($month);
        return $this->json($advices);
    }

    /**
     * Enregistrement d'un conseil
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('api/conseil', name: 'app_register_advice', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil')]
    public function AddAdvices( Request $request,EntityManagerInterface $em, 
                                SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $advice = new Advice();
        $advice = $serializer->deserialize($request->getContent(), Advice::class, 'json');

        // On vérifie les erreurs
        $errors = $validator->validate($advice);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            //throw new HttpException(JsonResponse::HTTP_BAD_REQUEST, "La requête est invalide");
        }
        if($advice->getMonth() < 1 || $advice->getMonth() > 12){
            return new JsonResponse(['message' => 'Le mois doit être compris entre 1 et 12'], 400);
        }

        $em->persist($advice);
        $em->flush();

        return new JsonResponse(['message' => "L'enregistrement s'est bien déroulé"], 201);
    }

    #[Route('api/conseil{id}', name: 'app_delete_advice', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil')]
    public function deleteAdvices( int $id, Request $request, AdviceRepository $adviceRepository, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $advice = $adviceRepository->find($id);
        if($advice == null){
            return new JsonResponse(['message' => 'Le conseil n\'existe pas'], 404);
        }
        $adviceRepository->delete($id);
        return new JsonResponse(['message' => "Le conseil ". $id . " a été supprimée"], 201);
    }

    /**
     * modification d'un conseil
     *
     * @param int $id
     * @param Request $request
     * @param AdviceRepository $adviceRepository
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('api/conseil{id}', name: 'app_modify_advice', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un conseil')]
    public function modifyAdvices( int $id, Request $request, AdviceRepository $adviceRepository, EntityManagerInterface $em, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        $advice = $adviceRepository->find($id);
        if($advice === null){
            return new JsonResponse(['message' => 'Le conseil n\'existe pas'], 404);
        }
        $newAdvice = $serializer->deserialize($request->getContent(), Advice::class, 'json');
        $error = $validator->validate($newAdvice);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        if ($newAdvice->getTips()){
            $advice->setTips($newAdvice->getTips());
        }
        if ($newAdvice->getMonth()){
            $advice->setMonth($newAdvice->getMonth());
        }
        $em->persist($advice);
        $em->flush();
        return new JsonResponse(['message' => "Le conseil ". $id . " a été modifiée"], 201);
    }
}
