<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Repository\TaskRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TaskRepositoryInterface $taskRepository;
    private SerializerInterface $serializer;

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->taskRepository = TaskControllerTest::getContainer()->get(TaskRepositoryInterface::class);
        $this->serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $this->client->request('POST', '/api/login', content: json_encode(['email' => 'bob@test.com', 'password' => 'testUser12345']));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $token = $data['token'];

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    public function testGetTasks(): void
    {
        // Arrange
        $client = $this->client;

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
        $client = $this->client;
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $id = $task->getId();

        // Act
        $client->request('GET', "/api/task/" . $id);
        $taskResponse = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();
        assert($taskResponse["id"] === (string)$task->getId());
        assert($taskResponse["title"] === 'Task 1');
        assert($taskResponse["description"] === 'Task description 1');
    }

    public function testGetMissingTask(): void
    {
        // Arrange
        $client = $this->client;
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $client->request('GET', "/api/task/$fakeUuid");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Task not found');
    }

    public function testGetTaskNotOwner(): void
    {
        // Arrange
        $client = $this->client;
        $task = $this->taskRepository->findOneBy(['title' => 'Task 20']);
        $id = $task->getId();

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
        $client = $this->client;
        $serializer = $this->serializer;
        $taskRepository = $this->taskRepository;

        $task = new Task();
        $task->setTitle('Task 21');
        $task->setDescription('Task description 21');

        // Act
        $newTask = $serializer->serialize($task, 'json');
        $client->request('POST', '/api/task', content: $newTask);

        // Assert
        $newTaskRes = $taskRepository->findOneBy(['title' => 'Task 21']);
        $this->assertResponseRedirects("/api/task/{$newTaskRes->getId()}");
        $this->assertNotNull($newTaskRes);
        $this->assertEquals($task->getTitle(), $newTaskRes->getTitle());
        $this->assertEquals($task->getDescription(), $newTaskRes->getDescription());
    }

    public function testCreateSubTask(): void
    {
        // Arrange
        $client = $this->client;
        $serializer = $this->serializer;
        $taskRepository = $this->taskRepository;

        $parentTask = $taskRepository->findOneBy(['title' => 'Task 1']);
        $task = new Task();
        $task->setTitle('SubTask 22');
        $task->setDescription('Subtask description 22');

        // Act
        $newTask = $serializer->serialize($task, 'json');
        $client->request('POST', "/api/task?parent={$parentTask->getId()}", content: $newTask);

        // Assert
        $newTaskRes = $taskRepository->findOneBy(['parent' => "{$parentTask->getId()}"]);
        $this->assertNotNull($newTaskRes);
        $this->assertResponseRedirects("/api/task/{$newTaskRes->getId()}");
        $this->assertEquals($task->getTitle(), $newTaskRes->getTitle());
        $this->assertEquals($task->getDescription(), $newTaskRes->getDescription());
        $this->assertEquals($parentTask->getId(), $newTaskRes->getParent()->getId());
    }

    public function testCreateTaskWithNullParent(): void
    {
        // Arrange
        $client = $this->client;
        $serializer = $this->serializer;

        $task = new Task();
        $task->setTitle('Task 22');
        $task->setDescription('Task description 22');
        $fakeUuid = Uuid::v4()->toRfc4122();

        $context = [
            'circular_reference_handler' => function ($object) {
                return method_exists($object, 'getId') ? $object->getId() : null;
            },
            'groups' => ['task:create'],
        ];

        // Act
        $newTask = $serializer->serialize($task, 'json', $context);
        $client->request('POST', "/api/task?parent=$fakeUuid", content: $newTask);
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Parent task not found');

    }

    public function testCreateTaskWithNoAccessToParent(): void
    {
        // Arrange
        $client = $this->client;
        $serializer = $this->serializer;

        $parentTask = $this->taskRepository->findOneBy(['title' => 'Task 11']);
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
        $client->request('POST', "/api/task?parent={$parentTask->getId()}", content: $newTask);
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        assert($response['error'] === 'Forbidden to access this resource');
    }

    public function testCreateTaskWithMissingTitle(): void
    {
        // Arrange
        $client = $this->client;
        $serializer = $this->serializer;
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
        $client = $this->client;
        $serializer = $this->serializer;
        $taskRepository = $this->taskRepository;

        $task = $taskRepository->findOneBy(['title' => 'Task 21']);
        $id = $task->getId();
        $updatedTask = new Task();
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
        $client = $this->client;
        $serializer = $this->serializer;
        $taskRepository = $this->taskRepository;

        $task = $taskRepository->findOneBy(['title' => 'Task 20']);
        $id = $task->getId();
        $updatedTask = new Task();
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
        $client = $this->client;
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $client->request('PUT', "/api/task/$fakeUuid");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Task not found');
    }

    public function testDeleteTask(): void
    {
        // Arrange
        $client = $this->client;
        $taskRepository = $this->taskRepository;
        $task = $taskRepository->findOneBy(['title' => 'Task 21 new']);
        $id = $task->getId();

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
        $client = $this->client;
        $taskRepository = $this->taskRepository;
        $task = $taskRepository->findOneBy(['title' => 'Task 20']);
        $id = $task->getId();

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
        $client = $this->client;
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $client->request('DELETE', "/api/task/$fakeUuid");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Task not found');
    }
}
