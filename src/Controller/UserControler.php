<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use App\Entity\Location;
use App\Entity\User;
use App\Entity\InfoUser;
use App\Repository\InfoUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControler extends AbstractController
{
    #[Route('/api/user', name: 'app_user_controler', methods: ['POST'])]
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
        if(null == $content->getInfoUser()->getZipCode() || $content->getInfoUser()->getZipCode() < 1000 || $content->getInfoUser()->getZipCode() > 1000000){
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

        $user = new User();
        $user = $content;
        $user->setPassword($userPasswordHasher->hashPassword($user, $content->getPassword()));
        $em->persist($user);
        $em->flush();   

        $infoUser = $infoUserRepository->findOneByUser($content);
        $infoUser->setCity($arrayCity[0]['city']);
        $infoUser->setCountry('FR');
        $em->persist($infoUser);
        $em->flush();

        $em->persist($infoUser);
        $em->flush();


       return new JsonResponse(['message' => 'Vous êtes bien enregistré !\n 
                    Vous pouvez vous connecter avec votre email et votre mot de passe : \n
                    http://ecogarden.test/api/auth'], 200);
    }

    
}
