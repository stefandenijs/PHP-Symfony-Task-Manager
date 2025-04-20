<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository implements TagRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function createOrUpdate(Tag $tag): void
    {
        $em = $this->getEntityManager();
        $em->persist($tag);
        $em->flush();
    }

    public function delete(Tag $tag): void
    {
        $em = $this->getEntityManager();
        $em->remove($tag);
        $em->flush();
    }
}
