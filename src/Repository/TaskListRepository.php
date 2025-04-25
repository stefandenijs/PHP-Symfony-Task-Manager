<?php

namespace App\Repository;

use App\Entity\TaskList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TaskList>
 */
class TaskListRepository extends ServiceEntityRepository implements TaskListRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskList::class);
    }

    public function createOrUpdate(TaskList $taskList): void
    {
        $em = $this->getEntityManager();
        $em->persist($taskList);
        $em->flush();
    }

    public function delete(TaskList $taskList): void
    {
        $em = $this->getEntityManager();
        $em->remove($taskList);
        $em->flush();
    }
}
