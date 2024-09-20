<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use app\Entity\User;
use App\Entity\DataUser;
use App\Entity\InfoUser;
use App\Entity\Advice;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
            $user1 = new User();
            $user1->setEmail('admin@test.com');
            $user1->setPassword($this->userPasswordHasher->hashPassword($user1,'test'));
            $user1->setRoles(['ROLE_ADMIN']);
            $manager->persist($user1);
            $dataUser = new InfoUser();
            $dataUser->setUser($user1);
            $dataUser->setZipCode(97115);
            $dataUser->setCountry('FR');
            $dataUser->setFirstName('super');
            $dataUser->setLastName('Admin');
            $dataUser->setCity('gwadatown');

            $manager->flush();

            for($i = 0; $i < 10; $i++){
                $user = new User();
                $user->setEmail($i . 'user@test.com');
                $user->setPassword($this->userPasswordHasher->hashPassword($user,'test'));
                $user->setRoles(['ROLE_USER']);
                $manager->persist($user);

                $dataUser = new InfoUser();
                $dataUser->setUser($user);
                $dataUser->setZipCode(25200+$i*2000);
                $dataUser->setCountry('FR');
                $dataUser->setFirstName('prénom' . $i);
                $dataUser->setLastName('Nom' . $i);
                $dataUser->setCity('Ville' . $i);
                
                $manager->persist($dataUser);
            }
            $manager->flush();

            for($i = 0; $i < 30; $i++){
                $advice = new Advice();
                $advice->setTips('tips' . $i);
                $advice->setMonth(random_int(1,12));

                $manager->persist($advice);
            }

            $manager->flush();
    }
}
