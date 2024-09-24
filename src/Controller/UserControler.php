<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\Location;
use App\Entity\User;
use App\Repository\InfoUserRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Requirement\Requirement;

class UserControler extends AbstractController
{

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
    #[Route('/api/user', name: 'app_user_add', methods: ['POST'])]
    public function newUser(Request $request, HttpClientInterface $httpClient, 
                            SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $em, 
                            InfoUserRepository $infoUserRepository, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        // récupération des données json et désérialisation
        $jsonContent = $request->getContent();
        $content = $serializer->deserialize($jsonContent, User::class, 'json', [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
        
        // vérification des champs obligatoires
        if(!isset($content)){
            return new JsonResponse(['message' => 'Aucune donnée reçue'], 400);
        }
        if(null == $content->getEmail() || null == $content->getPassword()){
            return new JsonResponse(['message' => 'Les champs email et password sont obligatoires'], 400);
        }
        if(null == $content->getInfoUser()->getFirstName() || null == $content->getInfoUser()->getLastName()){
            return new JsonResponse(['message' => 'Les champs firstName et lastName sont obligatoires'], 400);
        }
        if(null == $content->getInfoUser()->getZipCode() || $content->getInfoUser()->getZipCode() < 1000 || 
            $content->getInfoUser()->getZipCode() > 1000000){
            return new JsonResponse(['message' => 'Le code postal est obligatoire, suppérieur à 1000 et inférieur à 1000000'], 400);
        }
        // ajout du champs city s'il est null
        if(null == $content->getInfoUser()->getCity()){
            if( $content->getInfoUser()->getZipCode() < 10000){
                $zipCode = "0" . $content->getInfoUser()->getZipCode();
            }else{
                $zipCode = $content->getInfoUser()->getZipCode();
            }
            $response = $httpClient->request(
                'GET',
                'https://vicopo.selfbuild.fr/cherche/'. $zipCode,
            );
            // https://vicopo.selfbuild.fr/ pour plus d'informations

            $jsonreponse = $response->getContent();
            $location = $serializer->deserialize($jsonreponse, Location::class, 'json',[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
            // On vérifie les erreurs
            $errors = $validator->validate($location);
            if ($errors->count() > 0) {
                return new JsonResponse(['message' => 'Le code postal '.$content->getInfoUser()->getZipCode().' n\'est pas valide'], 400);
            }
            $arrayCity = $location->getCities();
            if(!count($arrayCity) > 0){
                return new JsonResponse(['message' => 'Le code postal '.$content->getInfoUser()->getZipCode().' n\'est pas valide'], 400);
            }
        }

        // enregistrement de l'utilisateur et hashage du mot de passe
        $user = new User();
        $user = $content;
        $user->setPassword($userPasswordHasher->hashPassword($user, $content->getPassword()));
        $em->persist($user);
        $em->flush();   
        // enregistrement des infoUser 
        $infoUser = $infoUserRepository->findOneByUser($content);
        $infoUser->setCity($arrayCity[0]['city']); //on ne récupère que la première ville du tableau
        $infoUser->setCountry('FR');
        $em->persist($infoUser);
        $em->flush();

       return new JsonResponse(['message' => 'Vous êtes bien enregistré ! 
                    Vous pouvez vous connecter avec votre email et votre mot de passe : 
                    http://ecogarden.test/api/auth'], 200);
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
        echo $uuid;
        $user = $userRepository->find($uuid);
        if($user == null){
            return new JsonResponse(['message' => 'Le compte n\'existe pas'], 404);
        }
        $userRepository->deleteUserByUuid($uuid);
        return new JsonResponse(['message' => "Le compte ". $uuid . " a été supprimé"], 201);
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
            return new JsonResponse(['message' => 'Le compte n\'existe pas'], 404);
        }
        $userRepository->deleteUserByEmail($email);
        return new JsonResponse(['message' => "Le compte ". $email . " a été supprimé"], 201);
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
    #[Route('/api/user/{id}', name: 'app_user_modify', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un utilisateur')]
    public function modifyUser(int $id, Request $request, UserRepository $userRepository, 
                                EntityManagerInterface $em, SerializerInterface $serializer, 
                                ValidatorInterface $validator): JsonResponse
    {

        $user = $userRepository->find($id);
        if($user === null){
            return new JsonResponse(['message' => 'Le compte n\'existe pas'], 404);
        }   
        $userToModify = $serializer->deserialize($request->getContent(), User::class, 'json');
        $error = $validator->validate($userToModify);
        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        if ($userToModify->getEmail()){
            $user->setEmail($userToModify->getEmail());
        }
        if ($userToModify->getRoles()){
            $user->setRoles($userToModify->getRoles());
        }
        if($userToModify->getInfoUser()->getFirstName()){
            $user->getInfoUser()->setFirstName($userToModify->getInfoUser()->getFirstName());
        }
        if($userToModify->getInfoUser()->getLastName()){
            $user->getInfoUser()->setLastName($userToModify->getInfoUser()->getLastName());
        }
        if($userToModify->getInfoUser()->getZipCode()){
            $user->getInfoUser()->setZipCode($userToModify->getInfoUser()->getZipCode());
        }
        if($userToModify->getInfoUser()->getCity()){
            $user->getInfoUser()->setCity($userToModify->getInfoUser()->getCity());
        }
        if($userToModify->getInfoUser()->getCountry()){
            $user->getInfoUser()->setCountry($userToModify->getInfoUser()->getCountry());
        }
        $em->persist($user);
        $em->flush();
        return new JsonResponse(['message' => "Le compte ". $id . " a été modifié"], 201);
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
    #[Route('/api/users', name: 'app_user_modify', methods: ['GET'])]
    public function listAllUsers(UserRepository $userRepository):JsonResponse
    {
        //récupération des données de la bdd
        $users = $userRepository->findAll();
        //sélection des données utiles
        $i = 0;
        foreach ($users as $user) {
            $liteUser[$i]['email'] = $user->getEmail();
            $liteUser[$i]['id'] = $user->getId();
            $i++;
        }
        return $this->json($liteUser);
    }
}
