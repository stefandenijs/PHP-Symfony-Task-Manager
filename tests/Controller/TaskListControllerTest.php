<?php

namespace App\Tests\Controller;

use App\Repository\TaskListRepositoryInterface;
use App\Repository\TaskRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class TaskListControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TaskRepositoryInterface $taskRepository;
    private TaskListRepositoryInterface $taskListRepository;
    private SerializerInterface $serializer;

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->taskRepository = TaskControllerTest::getContainer()->get(TaskRepositoryInterface::class);
        $this->taskListRepository = TaskControllerTest::getContainer()->get(TaskListRepositoryInterface::class);
        $this->serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $this->client->request('POST', '/api/login', content: json_encode(['email' => 'bob@test.com', 'password' => 'testUser12345']));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $token = $data['token'];

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    public function testGetListsReturnsUserLists()
    {
        // Act
        $this->client->request('GET', '/api/task-list');
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response[0]);
        $this->assertArrayHasKey('name', $response[0]);
        $this->assertArrayHasKey('tasks', $response[0]);
        $this->assertArrayHasKey('ownerId', $response[0]);
    }

    public function testGetListByIdReturnsListForOwner()
    {
        // Arrange
        $taskListId = $this->taskListRepository->findOneBy(['name' => 'List 1'])->getId();

        // Act
        $this->client->request('GET', "/api/task-list/$taskListId");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('tasks', $response);
    }

    public function testGetListByIdReturnsNotFound()
    {
        // Arrange
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $this->client->request('GET', "/api/task-list/$fakeUuid");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('List not found', $response['error']);
    }

    public function testGetListByIdReturnsForbidden()
    {
        // Arrange
        $taskListId = $this->taskListRepository->findOneBy(['name' => 'List 4'])->getId();

        // Act
        $this->client->request('GET', "/api/task-list/$taskListId");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testCreateTaskListSuccessfully()
    {
        // Arrange
        $testData = [
            'name' => 'List 7',
        ];

        // Act
        $this->client->request('POST', '/api/task-list', content: json_encode($testData));

        // Assert
        $newTaskList = $this->taskListRepository->findOneBy(['name' => 'List 7']);
        $this->assertEquals(303, $this->client->getResponse()->getStatusCode());
        $this->assertResponseRedirects();
        $this->assertEquals($testData['name'], $newTaskList->getName());
    }

    public function testCreateTaskListWithInvalidData()
    {
        // Arrange
        $testData = [];

        // Act
        $this->client->request('POST', '/api/task-list', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('field', $response[0]);
        $this->assertArrayHasKey('message', $response[0]);
        $this->assertArrayHasKey('code', $response[0]);
        $this->assertEquals('name', $response[0]['field']);
        $this->assertEquals('Task list name is required', $response[0]['message']);
    }

    /**
     * @depends testEditListSuccessfully
     */
    public function testDeleteTaskListSuccessfully()
    {
        // Arrange
        $taskListId = $this->taskListRepository->findOneBy(['name' => 'List 3'])->getId();

        // Act
        $this->client->request('DELETE', "/api/task-list/$taskListId");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
        $this->assertEquals('List deleted successfully', $response['success']);
    }

    public function testDeleteTaskListNotFound()
    {
        // Arrange
        $taskListId = Uuid::v4()->toRfc4122();

        // Act
        $this->client->request('DELETE', "/api/task-list/$taskListId");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('List not found', $response['error']);
    }

    public function testDeleteTaskListForbidden()
    {
        // Arrange
        $forbiddenListId = $this->taskListRepository->findOneBy(['name' => 'List 4'])->getId();

        // Act
        $this->client->request('DELETE', "/api/task-list/$forbiddenListId");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    /**
     * @depends testCreateTaskListSuccessfully
     */
    public function testEditListSuccessfully()
    {
        // Arrange
        $taskListId = $this->taskListRepository->findOneBy(['name' => 'List 7'])->getId();
        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'Task 1'])->getId();
        $taskIdTwo = $this->taskRepository->findOneBy(['title' => 'Task 2'])->getId();
        $testData = [
            'taskIds' => [$taskIdOne, $taskIdTwo],
            'name' => 'Updated list 7'
        ];

        // Act
        $this->client->request('PUT', "/api/task-list/$taskListId", content: json_encode($testData));

        // Assert
        $updatedTaskList = $this->taskListRepository->findOneBy(['name' => 'Updated list 7']);
        $this->assertEquals(303, $this->client->getResponse()->getStatusCode());
        $this->assertResponseRedirects();
        $tasks = $updatedTaskList->getTasks();
        $this->assertEquals($taskIdOne, $tasks[0]->getId());
        $this->assertEquals($taskIdTwo, $tasks[1]->getId());
        $this->assertEquals($testData['name'], $updatedTaskList->getName());
    }

    public function testEditListNotFound()
    {
        // Arrange
        $fakeUuid = Uuid::v4()->toRfc4122();
        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'Task 1'])->getId();

        $testData = [
            'taskIds' => [$taskIdOne],
            'name' => 'Updated list'
        ];

        // Act
        $this->client->request('PUT', "/api/task-list/$fakeUuid", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('List not found', $response['error']);
    }

    public function testEditListForbiddenListOwnership()
    {
        // Arrange
        $forbiddenListId = $this->taskListRepository->findOneBy(['name' => 'List 4'])->getId();

        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'Task 1'])->getId();

        $testData = [
            'taskIds' => [$taskIdOne],
            'name' => 'Updated list 4'
        ];

        // Act
        $this->client->request('PUT', "/api/task-list/$forbiddenListId", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testEditListForbiddenTaskOwnership()
    {
        // Arrange
        $taskListId = $this->taskListRepository->findOneBy(['name' => 'List 1'])->getId();
        $forbiddenTaskId = $this->taskRepository->findOneBy(['title' => 'Task 12'])->getId();
        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'Task 1'])->getId();

        $testData = [
            'taskIds' => [$taskIdOne, $forbiddenTaskId],
            'name' => 'Updated list 1'
        ];

        // Act
        $this->client->request('PUT', "/api/task-list/$taskListId", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    // TODO: Functionally useless for now as the name is never passed due to a null check, so it never hits the validator, which for now is the only invalid state.
//    public function testEditListWithValidationErrors()
//    {
//        // Arrange
//        $taskListId = $this->taskListRepository->findOneBy(['name' => 'Updated list 7'])->getId();
//        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'Task 1'])->getId();
//        $taskIdTwo = $this->taskRepository->findOneBy(['title' => 'Task 2'])->getId();
//
//        $testData = [
//            'taskIds' => [$taskIdOne, $taskIdTwo],
//            'name' => null
//        ];
//
//        // Act
//        $this->client->request('PUT', "/api/task-list/$taskListId", content: json_encode($testData));
//        $response = json_decode($this->client->getResponse()->getContent(), true);
//
//        // Assert
//        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
//        $this->assertIsArray($response);
//        $this->assertArrayHasKey('field', $response[0]);
//        $this->assertArrayHasKey('message', $response[0]);
//        $this->assertArrayHasKey('code', $response[0]);
//        $this->assertEquals('Task list name is required', $response[0]['message']);
//    }

    public function testRemoveTasksFromListSuccessfully()
    {
        // Arrange
        $taskListId = $this->taskListRepository->findOneBy(['name' => 'List 1'])->getId();
        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'List 1 task 1'])->getId();
        $taskIdTwo = $this->taskRepository->findOneBy(['title' => 'List 1 task 2'])->getId();
        $taskIdThree = $this->taskRepository->findOneBy(['title' => 'List 1 task 3'])->getId();

        $testData = [
            'taskIds' => [$taskIdOne, $taskIdTwo, $taskIdThree],
        ];

        // Act
        $this->client->request('DELETE', "/api/task-list/$taskListId/tasks", content: json_encode($testData));

        // Assert
        $updatedTaskList = $this->taskListRepository->find($taskListId);
        $this->assertEquals(303, $this->client->getResponse()->getStatusCode());
        $this->assertResponseRedirects();
        $tasks = $updatedTaskList->getTasks();

        foreach ($testData['taskIds'] as $taskId) {
            $this->assertFalse($tasks->contains($this->taskRepository->find($taskId)));
        }

        $this->assertEquals('List 1', $updatedTaskList->getName());
    }

    public function testRemoveTasksFromListNotFound()
    {
        // Arrange
        $fakeUuid = Uuid::v4()->toRfc4122();
        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'List 1 task 1'])->getId();
        $taskIdTwo = $this->taskRepository->findOneBy(['title' => 'List 1 task 2'])->getId();
        $taskIdThree = $this->taskRepository->findOneBy(['title' => 'List 1 task 3'])->getId();

        $testData = [
            'taskIds' => [$taskIdOne, $taskIdTwo, $taskIdThree],
        ];

        // Act
        $this->client->request('DELETE', "/api/task-list/$fakeUuid/tasks", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('List not found', $response['error']);
    }

    public function testRemoveTasksFromListForbidden()
    {
        // Arrange
        $taskListId = $this->taskListRepository->findOneBy(['name' => 'List 4'])->getId();
        $taskIdOne = $this->taskRepository->findOneBy(['title' => 'List 4 task 1'])->getId();
        $taskIdTwo = $this->taskRepository->findOneBy(['title' => 'List 4 task 2'])->getId();
        $taskIdThree = $this->taskRepository->findOneBy(['title' => 'List 4 task 3'])->getId();

        $testData = [
            'taskIds' => [$taskIdOne, $taskIdTwo, $taskIdThree],
        ];

        // Act
        $this->client->request('DELETE', "/api/task-list/$taskListId/tasks", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $this->assertIsArray($response);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }
}
