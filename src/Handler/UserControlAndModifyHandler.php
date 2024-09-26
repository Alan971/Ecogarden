<?php

namespace App\Handler;

use App\Entity\User;
use App\Entity\Location;
use App\Manager\UserManager;
use App\Manager\InfoUserManager;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class UserControlAndModifyHandler
{
    public function __construct(
    private UserManager $userManager,
    private InfoUserManager $infoUserManager,
    private SerializerInterface $serializer,
    private UserPasswordHasherInterface $userPasswordHasher,
    private ValidatorInterface $validator,
    private HttpClientInterface $httpClient
    )
    {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->httpClient = $httpClient;
    }

    /**
     * mise à jour d'un utilisateur
     * utilisation de la classe UserManager qui étend la classe BaseManager qui permet d'utiliser 
     * le set de méthodes de la classe baseManager en lieu et place de les méthodes natives de doctrine
     * @param string $id
     * @param [type] $request
     * @return void
     */
    public function userEdit(string $id, $request)
    {
        $id = Uuid::fromString($id);
        $user = $this->userManager->find($id);
        if($user === null){
            return ['message' => 'Le compte n\'existe pas', 'status' => 404];
        }  
        $userToModify = $this->serializer->deserialize($request, User::class, 'json');
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
        // if($userToModify->getInfoUser()->getCountry()){
        //     $user->getInfoUser()->setCountry($userToModify->getInfoUser()->getCountry());
        // }
        if($userToModify->getPassword()){
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $userToModify->getPassword()));
        }
        
        $this->userManager->persistAndFlush($user);
        return ['message' => "Le compte ". $id . " a été modifié", 'status' => 201];
    }

    /**
     * ajout d'un utilisateur
     * utilisation de la classe UserManager qui étend la classe BaseManager qui permet d'utiliser 
     * le set de méthodes de la classe baseManager en lieu et place de les méthodes natives de doctrine
     * @param string $id
     * @return void
     */
    public function userAdd($request)
    {
        $userToAdd = $this->serializer->deserialize($request, User::class, 'json');
        if (!isset($userToAdd)){
            return ['message' => 'Aucune donnée reçue', 'status' => 400];
        }
        if ($userToAdd->getEmail()){
            $user = $this->userManager->findOneBy(['email' => $userToAdd->getEmail()]);
            if($user != null){
                return ['message' => 'L\'email '. $userToAdd->getEmail() . ' est déjà utilisé', 'status' => 400];
            }
        }
        else{
            return ['message' => 'L\'email est obligatoire', 'status' => 400];
        }
        if (!$userToAdd->getPassword()){
            return ['message' => 'Le mot de passe est obligatoire', 'status' => 400];
        }
        if(null == $userToAdd->getInfoUser()->getFirstName() || null == $userToAdd->getInfoUser()->getLastName()){
            return ['message' => 'Les champs firstName et lastName sont obligatoires', 'status' => 400];
        }
        if(null == $userToAdd->getInfoUser()->getZipCode() || $userToAdd->getInfoUser()->getZipCode() < 1000 || 
            $userToAdd->getInfoUser()->getZipCode() > 1000000){
            return ['message' => 'Le code postal est obligatoire, suppérieur à 1000 et inférieur à 1000000', 'status' => 400];
        }
        // ajout du champs city s'il est null
        if(null == $userToAdd->getInfoUser()->getCity()){
            if( $userToAdd->getInfoUser()->getZipCode() < 10000){
                $zipCode = "0" . $userToAdd->getInfoUser()->getZipCode();
            }else{
                $zipCode = $userToAdd->getInfoUser()->getZipCode();
            }
            $response = $this->httpClient->request(
                'GET',
                'https://vicopo.selfbuild.fr/cherche/'. $zipCode,
            );
            // https://vicopo.selfbuild.fr/ pour plus d'informations

            $location = $this->serializer->deserialize($response->getContent(), Location::class, 'json',[AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
            // On vérifie les erreurs            
            $errors = $this->validator->validate($location);
            if ($errors->count() > 0) {
                return ['message' => 'Le code postal '.$userToAdd->getInfoUser()->getZipCode().' n\'est pas valide', 'status' => 400];
            }
            $arrayCity = $location->getCities();
            if(count($arrayCity) == 0){
                return ['message' => 'Le code postal '.$userToAdd->getInfoUser()->getZipCode().' n\'est pas valide', 'status' => 400];
            }
        }
        // par défaut un utilisateur est un user et ne peut choisir d'autre role.
        // c'est à l'admin de choisir les autres roles
        $userToAdd->setRoles(['ROLE_USER']);
        // enregistrement de l'utilisateur et hashage du mot de passe
        $user = new User();
        $user = $userToAdd;
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $userToAdd->getPassword()));
        $this->userManager->persistAndFlush($user);

        // enregistrement des infoUser 
         $infoUser = $this->infoUserManager->findOneBy(['user' => $userToAdd->getid()]);
        $infoUser->setCity($arrayCity[0]['city']); //on ne récupère que la première ville du tableau
        //on considère que le site ne s'adresse qu'au français, pour l'instant
        $infoUser->setCountry('FR');
        $this->infoUserManager->persistAndFlush($infoUser);
        return ['message' => 'Vous êtes bien enregistré ! Vous pouvez vous connecter avec votre email et votre mot de passe : 
                    http://ecogarden.test/api/auth', 'status' => 200];
    }

}   