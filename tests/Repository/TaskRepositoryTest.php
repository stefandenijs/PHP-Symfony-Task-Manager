<?php

namespace App\Tests\Repository;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskRepositoryTest extends KernelTestCase
{
    private TaskRepository $repository;

    public function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(TaskRepository::class);
    }

    public function testCreate(): Task
    {
        // Arrange
        $task = new Task();
        $task->setTitle("Create Task");
        $task->setDescription("Create Task");

        // Act
        $this->repository->createOrUpdate($task);
        $newTask = $this->repository->find($task->getId());

        // Assert
        $this->assertnotnull($newTask);
        $this->assertEquals($task, $newTask);
        assert($newTask->getTitle() === "Create Task");
        assert($newTask->getDescription() === "Create Task");

        return $newTask;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(Task $task): Task
    {
        // Arrange
        $taskToUpdate = $this->repository->find($task->getId());
        $taskToUpdate->setTitle("Update Task");
        $taskToUpdate->setDescription("Update Task");

        // Act
        $this->repository->createOrUpdate($taskToUpdate);
        $updatedTask = $this->repository->find($task->getId());

        // Assert
        $this->assertEquals($taskToUpdate, $updatedTask);
        assert($updatedTask->getTitle() === "Update Task");
        assert($taskToUpdate->getDescription() === "Update Task");

        return $updatedTask;
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(Task $task): void
    {
        $taskToDelete = $this->repository->find($task->getId());
        $this->repository->delete($taskToDelete);

        $deletedTask = $this->repository->find($task->getId());

        $this->assertNull($deletedTask);
    }
}
