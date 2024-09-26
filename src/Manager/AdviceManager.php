<?php

namespace App\Manager;

use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AdviceManager extends BaseManager
{

    public function __construct(
        protected EntityManagerInterface $em,
        protected ContainerInterface $container,
        protected AdviceRepository $repo
    ) {}

}