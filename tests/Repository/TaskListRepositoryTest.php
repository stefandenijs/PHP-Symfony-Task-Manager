<?php

namespace App\Tests\Repository;

use App\Entity\Task;
use App\Entity\TaskList;
use App\Repository\TaskListRepositoryInterface;
use App\Repository\TaskRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskListRepositoryTest extends KernelTestCase
{

    private TaskRepositoryInterface $taskRepository;
    private TasklistRepositoryInterface $taskListRepository;
    private UserRepositoryInterface $userRepository;

    public function setUp(): void
    {
        self::bootKernel();

        $this->taskListRepository = self::getContainer()->get(TasklistRepositoryInterface::class);
        $this->taskRepository = self::getContainer()->get(TaskRepositoryInterface::class);
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);
    }

    public function testCreate(): TaskList
    {
        // Arrange
        $user = $this->userRepository->findOneByEmail("bob@test.com");
        $taskList = new TaskList();
        $taskList->setOwner($user);
        $taskList->setName("New task list");

        $task = new Task();
        $task->setTitle("New task list task");
        $task->setDescription("New task list task");
        $task->setOwner($user);

        // Act
        $this->taskRepository->createOrUpdate($task);
        $taskList->addTask($task);
        $this->taskListRepository->createOrUpdate($taskList);
        $newTaskList = $this->taskListRepository->find($taskList->getId());
        $newTask = $this->taskRepository->find($task->getId());

        // Assert
        $this->assertNotNull($newTaskList);
        $this->assertEquals($taskList, $newTaskList);
        $this->assertnotnull($newTask);
        $this->assertEquals($task, $newTask);
        assert($newTaskList->getTasks()->count() > 0);
        assert($newTaskList->getTasks()->contains($newTask));
        assert($newTaskList->getName() == "New task list");
        assert($newTask->getTitle() === "New task list task");
        assert($newTask->getDescription() === "New task list task");

        return $newTaskList;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(TaskList $taskList): TaskList
    {
        // Arrange
        $taskListToUpdate = $this->taskListRepository->find($taskList->getId());
        $taskListToUpdate->setName("Updated task list");

        // Act
        $this->taskListRepository->createOrUpdate($taskListToUpdate);
        $updatedTask = $this->taskListRepository->find($taskList->getId());

        // Assert
        $this->assertEquals($taskListToUpdate, $updatedTask);
        assert($updatedTask->getName() === "Updated task list");

        return $updatedTask;
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(TaskList $taskList): void
    {
        $taskToDelete = $this->taskListRepository->find($taskList->getId());
        $this->taskListRepository->delete($taskToDelete);

        $deletedTask = $this->taskListRepository->find($taskList->getId());

        $this->assertNull($deletedTask);
    }
}
