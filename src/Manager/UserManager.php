<?php

namespace App\Manager;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserManager extends BaseManager
{

    public function __construct(
        protected EntityManagerInterface $em,
        protected ContainerInterface $container,
        protected UserRepository $repo
    ) {}

}