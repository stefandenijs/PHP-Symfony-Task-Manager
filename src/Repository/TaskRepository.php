<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function create(Task $task): void
    {
        $em = $this->getEntityManager();
        $em->persist($task);
        $em->flush();
    }

    public function delete(Task $task): void
    {
        $em = $this->getEntityManager();
        $em->remove($task);
        $em->flush();
    }

    public function update(Task $task): void
    {
        $em = $this->getEntityManager();
        $em->persist($task);
        $em->flush();
    }

//        /**
//         * @return Task[] Returns an array of Task objects
//         */
//        public function findByExampleField($value): array
//        {
//            return $this->createQueryBuilder('t')
//                ->andWhere('t.exampleField = :val')
//                ->setParameter('val', $value)
//                ->orderBy('t.id', 'ASC')
//                ->setMaxResults(10)
//                ->getQuery()
//                ->getResult()
//            ;
//        }
//
//        public function findOneBySomeField($value): ?Task
//        {
//            return $this->createQueryBuilder('t')
//                ->andWhere('t.exampleField = :val')
//                ->setParameter('val', $value)
//                ->getQuery()
//                ->getOneOrNullResult()
//            ;
//        }
}
