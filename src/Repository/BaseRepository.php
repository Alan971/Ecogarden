<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class BaseRepository extends ServiceEntityRepository 
{

    public function findMany($criterias = array(), $orders = array(), $numbers = array(), $options = array())
    {
        $qb = $this->createQueryBuilder('o');

        return $qb;
    }

    public function findOne($criterias = array(), $options = array())
    {
        $qb = $this->createQueryBuilder('o');

        if(isset($criterias['id'])) {
            $qb->andWhere('o.id =:id')->setParameter('id', $criterias['id']);
        }

        return $qb;
    }

    public function getManyResult(QueryBuilder $qb)
    {
        return $qb->getQuery()->getResult();
    }

    public function getOneResult(QueryBuilder $qb)
    {
        return $qb->getQuery()->getOneOrNullResult();
    }

}