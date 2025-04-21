<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Repository\TagRepositoryInterface;
use App\Repository\TaskRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TaskRepositoryInterface $taskRepository;
    private TagRepositoryInterface $tagRepository;
    private SerializerInterface $serializer;

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->taskRepository = TaskControllerTest::getContainer()->get(TaskRepositoryInterface::class);
        $this->tagRepository = TaskControllerTest::getContainer()->get(TagRepositoryInterface::class);
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
        $this->assertCount(25, $tasks);
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
        $taskRepository = $this->taskRepository;

        $testData = [
            'title' => 'Task 21',
            'description' => 'Task description 21',
        ];

        // Act
        $client->request('POST', '/api/task', content: json_encode($testData));

        // Assert
        $newTaskRes = $taskRepository->findOneBy(['title' => 'Task 21']);
        $this->assertResponseRedirects("/api/task/{$newTaskRes->getId()}");
        $this->assertNotNull($newTaskRes);
        $this->assertEquals($testData['title'], $newTaskRes->getTitle());
        $this->assertEquals($testData['description'], $newTaskRes->getDescription());
    }

    public function testCreateSubTask(): void
    {
        // Arrange
        $client = $this->client;
        $taskRepository = $this->taskRepository;

        $parentTask = $taskRepository->findOneBy(['title' => 'Task 1']);

        $testData = [
            'title' => 'SubTask 22',
            'description' => 'Subtask description 22',
        ];

        // Act
        $client->request('POST', "/api/task?parent={$parentTask->getId()}", content: json_encode($testData));

        // Assert
        $newTaskRes = $taskRepository->findOneBy(['parent' => "{$parentTask->getId()}"]);
        $this->assertNotNull($newTaskRes);
        $this->assertResponseRedirects("/api/task/{$newTaskRes->getId()}");
        $this->assertEquals($testData['title'], $newTaskRes->getTitle());
        $this->assertEquals($testData['description'], $newTaskRes->getDescription());
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

        $testData = json_encode(['description' => 'Task description 21']);

        // Act
        $client->request('POST', '/api/task', content: $testData);

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        assert($response[0]['field'] === 'title');
        assert($response[0]['message'] === 'A valid task title is required');
    }

    /**
     * @depends testCreateTask
     */
    public function testUpdateTask(): void
    {
        // Arrange
        $client = $this->client;
        $serializer = $this->serializer;
        $taskRepository = $this->taskRepository;

        $task = $taskRepository->findOneBy(['title' => 'Task 21']);
        $id = $task->getId();

        $testData = [
            'title' => 'Task 21 new',
            'description' => 'Task description 21 new',
        ];

        // Act
        $client->request('PUT', "/api/task/$id", content: json_encode($testData));

        // Assert
        $updatedTaskRes = $taskRepository->findOneBy(['title' => 'Task 21 new']);
        $this->assertResponseRedirects("/api/task/$id");
        $this->assertNotNull($updatedTaskRes);
        $this->assertEquals($testData['title'], $updatedTaskRes->getTitle());
        $this->assertEquals($testData['description'], $updatedTaskRes->getDescription());
    }

    public function testUpdateTaskNotOwner(): void
    {
        // Arrange
        $client = $this->client;
        $taskRepository = $this->taskRepository;

        $task = $taskRepository->findOneBy(['title' => 'Task 20']);
        $id = $task->getId();

        $testData = json_encode(['title' => 'Task 20 new', 'description' => 'Task description 21']);

        // Act
        $client->request('PUT', "/api/task/$id", content: $testData);
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

    /**
     * @depends testUpdateTask
     */
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

    public function testAddTagsToTasks(): void
    {
        // Arrange
        $client = $this->client;
        $taskRepository = $this->taskRepository;

        $task1 = $taskRepository->findOneBy(['title' => 'Task 1']);
        $task2 = $taskRepository->findOneBy(['title' => 'Task 2']);
        $tasks = [$task1, $task2];
        $taskIds = array_map(fn($task) => $task->getId(), $tasks);

        $tagsData = [
            [
                'name' => 'Urgent',
                'colour' => '#FF0000'
            ],
            [
                'name' => 'Important',
                'colour' => '#0000FF'
            ]
        ];

        $data = [
            'taskIds' => $taskIds,
            'tags' => $tagsData
        ];

        // Act
        $client->request('POST', '/api/task/assign-tags', content: json_encode($data));

        // Assert
        $this->assertResponseIsSuccessful();
        $responseContent = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Tags assigned successfully', $responseContent['success']);

        foreach ($tasks as $task) {
            $taskTags = $task->getTags();
            $this->assertCount(count($tagsData), $taskTags);
        }
    }

    public function testAddTagsToTasksMissingTagsOrTaskIds(): void
    {
        // Arrange
        $client = $this->client;

        // Act
        $client->request('POST', '/api/task/assign-tags', content: json_encode([]));

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        assert($response['error'] === 'Task ids and tags are required');
    }

    public function testAddTagsToTasksInvalidTaskIds(): void
    {
        // Arrange
        $client = $this->client;
        $invalidTaskIds = [Uuid::v4()->toRfc4122()];
        $tagsData = [
            ['name' => 'Urgent', 'colour' => '#FF0000']
        ];
        $data = [
            'taskIds' => $invalidTaskIds,
            'tags' => $tagsData
        ];

        // Act
        $client->request('POST', '/api/task/assign-tags', content: json_encode($data));

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(404);
        assert($response['error'] === 'Some task ids not found');
    }

    public function testAddTagsToTasksForbiddenAccess(): void
    {
        // Arrange
        $client = $this->client;
        $task = $this->taskRepository->findOneBy(['title' => 'Task 11']);

        $data = [
            'taskIds' => [$task->getId()],
            'tags' => [['name' => 'Urgent', 'colour' => '#FF0000']]
        ];

        // Act
        $client->request('POST', '/api/task/assign-tags', content: json_encode($data));

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(403);
        assert($response['error'] === 'Forbidden to access this resource');
    }

    public function testAddTagsToTasksMissingTagNameOrColour(): void
    {
        // Arrange
        $client = $this->client;
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $data = [
            'taskIds' => [$task->getId()],
            'tags' => [['name' => 'Urgent']]
        ];

        // Act
        $client->request('POST', '/api/task/assign-tags', content: json_encode($data));

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        assert($response['error'] === 'Each tag must have both name and colour');
    }

    public function testAddTagsToTasksInvalidTagValidation(): void
    {
        // Arrange
        $client = $this->client;
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $data = [
            'taskIds' => [$task->getId()],
            'tags' => [['name' => 'Urgent', 'colour' => 'invalidColour']]
        ];

        // Act
        $client->request('POST', '/api/task/assign-tags', content: json_encode($data));

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        assert($response[0]['field'] === 'colour');
        assert($response[0]['message'] === 'Tag colour should match a hex colour value');
    }

    public function testRemoveTagsFromTasks(): void
    {
        // Arrange
        $task1 = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $task2 = $this->taskRepository->findOneBy(['title' => 'Task 2']);
        $tasks = [$task1, $task2];
        $taskIds = array_map(fn($task) => $task->getId(), $tasks);

        $tag1 = $this->tagRepository->findOneBy(['name' => 'Tag 1']);
        $tag2 = $this->tagRepository->findOneBy(['name' => 'Tag 3']);
        $tags = [$tag1, $tag2];
        $tagIds = array_map(fn($tag) => $tag->getId(), $tags);

        $data = [
            'taskIds' => $taskIds,
            'tagIds' => $tagIds,
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($data));

        // Assert
        $this->assertResponseIsSuccessful();

        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Tags removed successfully', $responseContent['success']);

        foreach ($tasks as $task) {
            foreach ($tags as $tag) {
                $this->assertFalse($task->getTags()->contains($tag));
            }
        }
    }

    public function testRemoveTagsFromTasksMissingTaskIdsOrTagIds(): void
    {
        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode([]));

        // Assert
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals('Task ids and tag ids are required', $response['error']);
    }

    public function testRemoveTagsFromTasksInvalidTaskIds(): void
    {
        // Arrange
        $invalidTaskIds = [Uuid::v4()->toRfc4122()];
        $tag1 = $this->tagRepository->findOneBy(['name' => 'Tag 1']);
        $tagIds = [$tag1->getId()];

        $data = [
            'taskIds' => $invalidTaskIds,
            'tagIds' => $tagIds,
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($data));

        // Assert
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Some task ids not found', $response['error']);
    }

    public function testRemoveTagsFromTasksForbiddenAccess(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 11']);
        $tag1 = $this->tagRepository->findOneBy(['name' => 'Tag 1']);
        $data = [
            'taskIds' => [$task->getId()],
            'tagIds' => [$tag1->getId()],
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($data));

        // Assert
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testRemoveTagsFromTasksTagNotFound(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $invalidTagId = Uuid::v4()->toRfc4122();
        $data = [
            'taskIds' => [$task->getId()],
            'tagIds' => [$invalidTagId],
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($data));

        // Assert
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Tag not found', $response['error']);
    }

    public function testRemoveTagsFromTasksTagCreatedByAnotherUser(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 11']);
        $data = [
            'taskIds' => [$task->getId()],
            'tagIds' => [$tag->getId()],
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($data));

        // Assert
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Cannot modify tags created by another user', $response['error']);
    }
}
