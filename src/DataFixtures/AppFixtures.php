<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use app\Entity\User;
use App\Entity\DataUser;
use App\Entity\Advice;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
            $user1 = new User();
            $user1->setEmail('admin@test.com');
            $user1->setPassword('test');
            $user1->setRoles(['ROLE_ADMIN']);
            $manager->persist($user1);

            $manager->flush();

            for($i = 0; $i < 10; $i++){
                $user = new User();
                $user->setEmail($i . 'user@test.com');
                $user->setPassword('test');
                $user->setRoles(['ROLE_USER']);
                $manager->persist($user);

                $dataUser = new DataUser();
                $dataUser->setEmail($user);
                $dataUser->setPostcode(25200+$i*2000);
                $dataUser->setCountry('FR');
                $dataUser->setFirstname('prénom' . $i);
                $dataUser->setLastname('Nom' . $i);
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
