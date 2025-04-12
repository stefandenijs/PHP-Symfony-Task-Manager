<?php

namespace App\Tests\Controller;

use App\Controller\TaskController;
use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class TaskControllerTest extends WebTestCase
{
    public function testGetTasks(): void
    {
        // Arrange
        $client = TaskControllerTest::createClient();

        // Act
        $client->request('GET', '/task');
        $tasks = $client->getResponse()->getContent();
        $tasks = json_decode($tasks, true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertCount(10, $tasks);
        assert($tasks[0]["title"] === 'Task 1');
        assert($tasks[0]["description"] === 'Task description 1');
    }

    public function testGetTask(): void
    {
        // Arrange
        $client = TaskControllerTest::createClient();
        $id = 1;

        // Act
        $client->request('GET', "/task/" . $id);
        $task = $client->getResponse()->getContent();
        $task = json_decode($task, true);

        // Assert
        $this->assertResponseIsSuccessful();
        assert($task["id"] === $id);
        assert($task["title"] === 'Task 1');
        assert($task["description"] === 'Task description 1');
    }

    public function testCreateTask(): void
    {
        // Arrange
        $client = TaskControllerTest::createClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $taskRepository = TaskControllerTest::getContainer()->get(TaskRepository::class);

        $task = new Task();
        $task->setTitle('Task 11');
        $task->setDescription('Task description 11');

        // Act
        $newTask = $serializer->serialize($task, 'json');
        $client->request('POST', '/task', content: $newTask);

        // Assert
        $this->assertResponseRedirects('/task/11');
        $newTaskRes = $taskRepository->findOneBy(['title' => 'Task 11']);
        $this->assertNotNull($newTaskRes);
        $this->assertEquals($task->getTitle(), $newTaskRes->getTitle());
        $this->assertEquals($task->getDescription(), $newTaskRes->getDescription());
    }
}
