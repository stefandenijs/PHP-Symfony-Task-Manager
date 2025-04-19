<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class TaskControllerTest extends WebTestCase
{
    protected function setUpClient(): KernelBrowser
    {
        $client = TaskControllerTest::createClient();
        $client->request('POST', '/api/login', content: json_encode(['email' => 'test@test.com', 'password' => 'testUser12345']));

        $data = json_decode($client->getResponse()->getContent(), true);
        $token = $data['token'];


        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));

        return $client;
    }

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

    public function testGetTaskNotOwner(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $id = 20;

        // Act
        $client->request('GET', "/api/task/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        assert($response['error'] === 'Forbidden to access this resource');

    }

    public function testCreateTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $taskRepository = TaskControllerTest::getContainer()->get(TaskRepository::class);

        $task = new Task();
        $task->setTitle('Task 21');
        $task->setDescription('Task description 21');

        // Act
        $newTask = $serializer->serialize($task, 'json');
        $client->request('POST', '/api/task', content: $newTask);

        // Assert
        $this->assertResponseRedirects('/api/task/21');
        $newTaskRes = $taskRepository->findOneBy(['title' => 'Task 21']);
        $this->assertNotNull($newTaskRes);
        $this->assertEquals($task->getTitle(), $newTaskRes->getTitle());
        $this->assertEquals($task->getDescription(), $newTaskRes->getDescription());
    }

    public function testCreateSubTask(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $taskRepository = TaskControllerTest::getContainer()->get(TaskRepository::class);

        $task = new Task();
        $task->setTitle('SubTask 22');
        $task->setDescription('Subtask description 22');

        // Act
        $newTask = $serializer->serialize($task, 'json');
        $client->request('POST', '/api/task?parent=1', content: $newTask);

        // Assert
        $this->assertResponseRedirects('/api/task/22');
        $newTaskRes = $taskRepository->findOneBy(['parent' => '1']);
        $this->assertNotNull($newTaskRes);
        $this->assertEquals($task->getTitle(), $newTaskRes->getTitle());
        $this->assertEquals($task->getDescription(), $newTaskRes->getDescription());
        $this->assertEquals(1, $newTaskRes->getParent()->getId());
    }

    public function testCreateTaskWithNullParent(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);

        $task = new Task();
        $task->setTitle('Task 22');
        $task->setDescription('Task description 22');

        $context = [
            'circular_reference_handler' => function ($object) {
                return method_exists($object, 'getId') ? $object->getId() : null;
            },
            'groups' => ['task:create'],
        ];

        // Act
        $newTask = $serializer->serialize($task, 'json', $context);
        $client->request('POST', '/api/task?parent=999', content: $newTask);
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Parent task not found');

    }

    public function testCreateTaskWithNoAccessToParent(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);

        $task = new Task();
        $task->setTitle('Task 22');
        $task->setDescription('Task description 22');

        $context = [
            'circular_reference_handler' => function ($object) {
            return method_exists($object, 'getId') ? $object->getId() : null;
            },
            'groups' => ['task:create'],
        ];

        // Act
        $newTask = $serializer->serialize($task, 'json', $context);
        $client->request('POST', '/api/task?parent=20', content: $newTask);
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        assert($response['error'] === 'Forbidden to access this resource');
    }

    public function testCreateTaskWithMissingTitle(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $task = new Task();
        $task->setDescription('Task description 21');

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
        $id = 21;
        $updatedTask->setTitle('Task 21 new');
        $updatedTask->setDescription('Task description 21 new');

        // Act
        $requestUpdatedTask = $serializer->serialize($updatedTask, 'json');
        $client->request('PUT', "/api/task/$id", content: $requestUpdatedTask);

        // Assert
        $updatedTaskRes = $taskRepository->findOneBy(['title' => 'Task 21 new']);
        $this->assertResponseRedirects("/api/task/$id");
        $this->assertNotNull($updatedTaskRes);
        $this->assertEquals($updatedTask->getTitle(), $updatedTaskRes->getTitle());
        $this->assertEquals($updatedTask->getDescription(), $updatedTaskRes->getDescription());
    }

    public function testUpdateTaskNotOwner(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $updatedTask = new Task();
        $id = 20;
        $updatedTask->setTitle('Task 20 new');
        $updatedTask->setDescription('Task description 20 new');

        // Act
        $requestUpdatedTask = $serializer->serialize($updatedTask, 'json');
        $client->request('PUT', "/api/task/$id", content: $requestUpdatedTask);
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        assert($response['error'] === 'Forbidden to access this resource');
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
        $id = 21;

        // Act
        $client->request('DELETE', "/api/task/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        assert($response['success'] === 'Task deleted successfully');
    }

    public function testDeleteTaskNotOwner(): void
    {
        // Arrange
        $client = $this->setUpClient();
        $id = 20;

        // Act
        $client->request('DELETE', "/api/task/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        assert($response['error'] === 'Forbidden to access this resource');
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
