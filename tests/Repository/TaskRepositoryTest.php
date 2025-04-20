<?php

namespace App\Tests\Repository;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskRepositoryTest extends KernelTestCase
{
    private TaskRepository $taskRepository;
    private UserRepository $userRepository;

    public function setUp(): void
    {
        self::bootKernel();

        $this->taskRepository = self::getContainer()->get(TaskRepository::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);

    }

    public function testCreate(): Task
    {
        // Arrange
        $user = $this->userRepository->findOneByEmail("bob@test.com");
        $task = new Task();
        $task->setTitle("Create Task");
        $task->setDescription("Create Task");
        $task->setOwner($user);

        // Act
        $this->taskRepository->createOrUpdate($task);
        $newTask = $this->taskRepository->find($task->getId());

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
        $taskToUpdate = $this->taskRepository->find($task->getId());
        $taskToUpdate->setTitle("Update Task");
        $taskToUpdate->setDescription("Update Task");

        // Act
        $this->taskRepository->createOrUpdate($taskToUpdate);
        $updatedTask = $this->taskRepository->find($task->getId());

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
        $taskToDelete = $this->taskRepository->find($task->getId());
        $this->taskRepository->delete($taskToDelete);

        $deletedTask = $this->taskRepository->find($task->getId());

        $this->assertNull($deletedTask);
    }
}
