<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Handler\UserControlAndModifyHandler;
use App\Repository\InfoUserRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\HttpFoundation\Response;

class UserControler extends AbstractController
{
    public function __construct(private UserRepository $userRepository,
                                private EntityManagerInterface $em,
                                private SerializerInterface $serializer,
                                private ValidatorInterface $validator,
                                private UserControlAndModifyHandler $userControlAndModify)
    {
    }
    /**
     * Methode permettant de créer un nouvel utilisateur
     *
     * @param Request $request
     * @param HttpClientInterface $httpClient
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param EntityManagerInterface $em
     * @param InfoUserRepository $infoUserRepository
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')]
    #[Route('/api/user', name: 'app_user_add', methods: ['POST'])]
    public function newUser(Request $request): JsonResponse
    {
        $answerArray = $this->userControlAndModify->userAdd($request->getContent());
        return new JsonResponse(['message' => $answerArray['message'], 'status' => $answerArray['status']], $answerArray['status']);
    }
    /**
     * Methode permettant de supprimer un utilisateur
     * 
     * @param string $uuid
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    #[Route('/api/user/{uuid}', requirements: ['uuid' => Requirement::UUID], name: 'app_user_delete_uuid', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')]
    public function deleteUserByUuid(string $uuid, UserRepository $userRepository): JsonResponse
    {
        $uuid = Uuid::fromString($uuid);
        $user = $userRepository->find($uuid);
        if($user == null){
            return new JsonResponse(['message' => 'Le compte n\'existe pas', 'status' => '404'], 404);
        }
        $userRepository->deleteUserByUuid($uuid);
        return new JsonResponse(['message' => "Le compte ". $uuid . " a été supprimé", 'status' => '201'], 201);
    }
    /**
     * Methode permettant de supprimer un utilisateur
     *  Attention ! le chemin de l'url doit être /api/e-user/{email}
     * @param string $email
     * @param UserRepository $userRepository
     * @return JsonResponse
     */
    #[Route('/api/e-user/{email}', name: 'app_user_delete_email', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')]
    public function deleteUserByEmail(string $email, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->findOneByEmail($email);
        if($user == null){
            return new JsonResponse(['message' => 'Le compte n\'existe pas', 'status' => '404'], 404);
        }
        $userRepository->deleteUserByEmail($email);
        return new JsonResponse(['message' => "Le compte ". $email . " a été supprimé", 'status' => '201'], 201);
    }

    /**
     * Methode permettant de modifier un utilisateur
     *
     * @param int $id
     * @param Request $request
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $em
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @return JsonResponse
     */
    #[Route('/api/user/{uuid}', name: 'app_user_modify', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un utilisateur')]
    public function modifyUser(string $uuid, Request $request): JsonResponse
    {
        $answerArray = $this->userControlAndModify->userEdit($uuid, $request->getContent());
        return new JsonResponse(['message' => $answerArray['message'], 'status' => $answerArray['status']], $answerArray['status']);

    }

    /**
     * Methode permettant de récupérer la liste des utilisateurs
     * utilisée par l'admin pour connaitre Uuid des utilisateurs qu'il souhaite supprimer ou modifier
     * 
     * @param UserRepository $userRepository
     * @param SerializerInterface $serializer
     * @return JsonResponse
     */
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un utilisateur')]
    #[Route('/api/users', name: 'app_user_view', methods: ['GET'])]
    public function listAllUsers(UserRepository $userRepository, SerializerInterface $serializer):JsonResponse
    {
        $users = $userRepository->findAll();
        $jsonUsers = $serializer->serialize($users, 'json', ['groups' => ['getAllUsers']]);
        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }
}
