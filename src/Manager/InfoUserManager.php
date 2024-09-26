<?php

namespace App\Manager;

use App\Repository\InfoUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InfoUserManager extends BaseManager
{

    public function __construct(
        protected EntityManagerInterface $em,
        protected ContainerInterface $container,
        protected InfoUserRepository $repo
    ) {}

}