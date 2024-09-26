<?php

namespace App\Manager;


use App\Repository\RepositoryInterface;
use App\Traits\Containerable;
use Doctrine\ORM\EntityManagerInterface;

abstract class BaseManager
{

    public function debug10($items) {

        $cpt = 0;
        foreach ($items as $key => $item) {
            dump($key, $item);
            $cpt++;
            if($cpt == 10) {
                break;
            }
        }
    }

    public function persist($entity)
    {
        $this->em->persist($entity);
    }

    public function flush()
    {
        $this->em->flush();
    }

    public function persistAndFlush($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    public function removeAndFlush($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository()
    {
        return $this->repo;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getManager()
    {
        return $this->em;
    }


    public function findMany($criterias = array(), $orders = array(), $numbers = array(), $options = array())
    {
        $qb = $this->repo->findMany($criterias, $orders, $numbers, $options);

        return (!isset($options['_locale'])) ? $this->repo->getManyResult($qb) : $this->repo->getManyResult($qb, $options['_locale']);
    }

    public function findOne($criterias = array(), $options = array())
    {
        $qb = $this->repo->findOne($criterias, $options);

        return (!isset($options['_locale'])) ? $this->repo->getOneResult($qb) : $this->repo->getOneResult($qb, $options['_locale']);
    }

    public function find($id)
    {
        return $this->repo->find($id);
    }

    public function findAll()
    {
        return $this->repo->findAll();
    }

    public function findBy($criterias = [], $orderBy = [], $limit = null, $offset = null)
    {
        return $this->repo->findBy($criterias, $orderBy, $limit, $offset);
    }

    public function findOneBy($criterias = [], $orderBy = [])
    {
        return $this->repo->findOneBy($criterias, $orderBy);
    }
    public function findByUuid($uuid)
    {
        return $this->repo->findOneByUuid($uuid);
    }   

    public function updateVisite($object, $_locale)
    {
        $object->setLocale($_locale);
        $object->setCount($object->getCount() + 1);
        $this->persistAndFlush($object);
    }

    public function findAllIdName()
    {
        $r = [];

        $items = $this->findAll();

        foreach ($items as $item) {
            $r[$item->getId()] = $item->getName();
        }

        return $r;
    }

}