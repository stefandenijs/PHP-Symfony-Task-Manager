<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class TaskControllerTest extends WebTestCase
{
    public function testGetTasks(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('GET', '/api/task');
        $tasks = $client->getResponse()->getContent();
        $tasks = json_decode($tasks, true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertCount(10, $tasks);
        assert($tasks[0]["title"] === 'Task 1');
        assert($tasks[0]["description"] === 'Task description 1');
    }

    public function setUpClient(): KernelBrowser
    {
        $client = TaskControllerTest::createClient();
        $client->request('POST', '/api/login', content: json_encode(['email' => 'test@test.com', 'password' => 'testUser12345']));

        $data = json_decode($client->getResponse()->getContent(), true);
        $token = $data['token'];


        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));

        return $client;
    }

    public function testGetTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $id = 1;

        // Act
        $client->request('GET', "/api/task/" . $id);
        $task = $client->getResponse()->getContent();
        $task = json_decode($task, true);

        // Assert
        $this->assertResponseIsSuccessful();
        assert($task["id"] === $id);
        assert($task["title"] === 'Task 1');
        assert($task["description"] === 'Task description 1');
    }

    public function testGetMissingTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $id = 999;

        // Act
        $client->request('GET', "/api/task/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Task not found');
    }

    public function testCreateTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $taskRepository = TaskControllerTest::getContainer()->get(TaskRepository::class);

        $task = new Task();
        $task->setTitle('Task 11');
        $task->setDescription('Task description 11');

        // Act
        $newTask = $serializer->serialize($task, 'json');
        $client->request('POST', '/api/task', content: $newTask);

        // Assert
        $this->assertResponseRedirects('/api/task/11');
        $newTaskRes = $taskRepository->findOneBy(['title' => 'Task 11']);
        $this->assertNotNull($newTaskRes);
        $this->assertEquals($task->getTitle(), $newTaskRes->getTitle());
        $this->assertEquals($task->getDescription(), $newTaskRes->getDescription());
    }

    public function testCreateTaskWithMissingTitle(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $task = new Task();
        $task->setDescription('Task description 11');

        // Act
        $newTask = $serializer->serialize($task, 'json');
        $client->request('POST', '/api/task', content: $newTask);

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        assert($response[0]['field'] === 'title');
        assert($response[0]['message'] === 'A valid task title is required');
    }

    public function testUpdateTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $taskRepository = TaskControllerTest::getContainer()->get(TaskRepository::class);

        $updatedTask = new Task();
        $id = 11;
        $updatedTask->setTitle('Task 11 new');
        $updatedTask->setDescription('Task description 11 new');

        // Act
        $requestUpdatedTask = $serializer->serialize($updatedTask, 'json');
        $client->request('PUT', "/api/task/$id", content: $requestUpdatedTask);

        // Assert
        $updatedTaskRes = $taskRepository->findOneBy(['title' => 'Task 11 new']);
        $this->assertResponseRedirects("/api/task/$id");
        $this->assertNotNull($updatedTaskRes);
        $this->assertEquals($updatedTask->getTitle(), $updatedTaskRes->getTitle());
        $this->assertEquals($updatedTask->getDescription(), $updatedTaskRes->getDescription());
    }

    public function testUpdateMissingTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $id = 999;

        // Act
        $client->request('PUT', "/api/task/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Task not found');
    }

    public function testDeleteTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $id = 11;

        // Act
        $client->request('DELETE', "/api/task/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        assert($response['success'] === 'Task deleted successfully');
    }

    public function testDeleteMissingTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $id = 999;

        // Act
        $client->request('DELETE', "/api/task/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Task not found');
    }
}
