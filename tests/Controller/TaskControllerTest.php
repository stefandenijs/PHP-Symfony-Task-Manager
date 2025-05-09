<?php

namespace App\Tests\Controller;

use App\Repository\TagRepositoryInterface;
use App\Repository\TaskRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class TaskControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TaskRepositoryInterface $taskRepository;
    private TagRepositoryInterface $tagRepository;

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->taskRepository = TaskControllerTest::getContainer()->get(TaskRepositoryInterface::class);
        $this->tagRepository = TaskControllerTest::getContainer()->get(TagRepositoryInterface::class);
        $this->client->request('POST', '/api/login', content: json_encode(['email' => 'bob@test.com', 'password' => 'testUser12345']));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $token = $data['token'];

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    public function testGetTasks(): void
    {
        // Act
        $this->client->request('GET', '/api/task');
        $tasks = $this->client->getResponse()->getContent();
        $tasks = json_decode($tasks, true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertCount(25, $tasks);
        $this->assertEquals('Task 1', $tasks[0]["title"]);
        $this->assertEquals('Task description 1', $tasks[0]["description"]);
    }

    public function testGetTask(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $id = $task->getId();

        // Act
        $this->client->request('GET', "/api/task/" . $id);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertEquals($response["id"] , (string)$task->getId());
        $this->assertEquals('Task 1', $response["title"]);
        $this->assertEquals('Task description 1', $response["description"]);
    }

    public function testGetMissingTask(): void
    {
        // Arrange
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $this->client->request('GET', "/api/task/$fakeUuid");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Task not found', $response['error']);
    }

    public function testGetTaskNotOwner(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 20']);
        $id = $task->getId();

        // Act
        $this->client->request('GET', "/api/task/$id");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);

    }

    public function testCreateTask(): void
    {
        // Arrange
        $testData = ['title' => 'Task 21', 'description' => 'Task description 21'];

        // Act
        $this->client->request('POST', '/api/task', content: json_encode($testData));

        // Assert
        $newTaskRes = $this->taskRepository->findOneBy(['title' => 'Task 21']);
        $this->assertResponseRedirects("/api/task/{$newTaskRes->getId()}");
        $this->assertNotNull($newTaskRes);
        $this->assertEquals($testData['title'], $newTaskRes->getTitle());
        $this->assertEquals($testData['description'], $newTaskRes->getDescription());
    }

    public function testCreateSubTask(): void
    {
        // Arrange
        $parentTask = $this->taskRepository->findOneBy(['title' => 'Task 1']);

        $testData = ['title' => 'SubTask 22', 'description' => 'Subtask description 22'];

        // Act
        $this->client->request('POST', "/api/task?parent={$parentTask->getId()}", content: json_encode($testData));

        // Assert
        $newTaskRes = $this->taskRepository->findOneBy(['parent' => "{$parentTask->getId()}", 'title' => 'SubTask 22']);
        $this->assertNotNull($newTaskRes);
        $this->assertResponseRedirects("/api/task/{$newTaskRes->getId()}");
        $this->assertEquals($testData['title'], $newTaskRes->getTitle());
        $this->assertEquals($testData['description'], $newTaskRes->getDescription());
        $this->assertEquals($parentTask->getId(), $newTaskRes->getParent()->getId());
    }

    public function testCreateTaskWithNullParent(): void
    {
        // Arrange
        $fakeUuid = Uuid::v4()->toRfc4122();

        $testData = ['title' => 'Task 22', 'description' => 'Task description 22'];

        // Act
        $this->client->request('POST', "/api/task?parent=$fakeUuid", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Parent task not found', $response['error']);

    }

    public function testCreateTaskWithNoAccessToParent(): void
    {
        // Arrange
        $parentTask = $this->taskRepository->findOneBy(['title' => 'Task 11']);
        $testData = ['title' => 'Task 22', 'description' => 'Task description 22'];

        // Ac
        $this->client->request('POST', "/api/task?parent={$parentTask->getId()}", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testCreateTaskWithMissingTitle(): void
    {
        // Arrange
        $testData = ['description' => 'Task description 21'];

        // Act
        $this->client->request('POST', '/api/task', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals('title', $response[0]['field']);
        $this->assertEquals('A valid task title is required', $response[0]['message']);
    }

    /**
     * @depends testCreateTask
     */
    public function testEditTask(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 21']);
        $id = $task->getId();

        $testData = [
            'title' => 'Task 21 new',
            'description' => 'Task description 21 new',
        ];

        // Act
        $this->client->request('PUT', "/api/task/$id", content: json_encode($testData));

        // Assert
        $updatedTaskRes = $this->taskRepository->findOneBy(['title' => 'Task 21 new']);
        $this->assertResponseRedirects("/api/task/$id");
        $this->assertNotNull($updatedTaskRes);
        $this->assertEquals($testData['title'], $updatedTaskRes->getTitle());
        $this->assertEquals($testData['description'], $updatedTaskRes->getDescription());
    }

    public function testEditTaskNotOwner(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 20']);
        $id = $task->getId();

        $testData = ['title' => 'Task 20 new', 'description' => 'Task description 21'];

        // Act
        $this->client->request('PUT', "/api/task/$id", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testEditMissingTask(): void
    {
        // Arrange
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $this->client->request('PUT', "/api/task/$fakeUuid");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Task not found', $response['error']);
    }

    /**
     * @depends testEditTask
     */
    public function testDeleteTask(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 21 new']);
        $id = $task->getId();

        // Act
        $this->client->request('DELETE', "/api/task/$id");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals('Task deleted successfully', $response['success']);
    }

    public function testDeleteTaskNotOwner(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 20']);
        $id = $task->getId();

        // Act
        $this->client->request('DELETE', "/api/task/$id");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }


    public function testDeleteMissingTask(): void
    {
        // Arrange
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $this->client->request('DELETE', "/api/task/$fakeUuid");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Task not found', $response['error']);
    }

    public function testAddTagsToTasks(): void
    {
        // Arrange
        $task1 = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $task2 = $this->taskRepository->findOneBy(['title' => 'Task 2']);
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

        $testData = [
            'taskIds' => $taskIds,
            'tags' => $tagsData
        ];

        // Act
        $this->client->request('POST', '/api/task/assign-tags', content: json_encode($testData));

        // Assert
        $this->assertResponseIsSuccessful();
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Tags assigned successfully', $responseContent['success']);

        foreach ($tasks as $task) {
            $taskTags = $task->getTags();
            $this->assertCount(count($tagsData), $taskTags);
        }
    }

    public function testAddTagsToTasksMissingTagsOrTaskIds(): void
    {
        // Act
        $this->client->request('POST', '/api/task/assign-tags', content: json_encode([]));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals('Task ids and tags are required', $response['error']);
    }

    public function testAddTagsToTasksInvalidTaskIds(): void
    {
        // Arrange
        $invalidTaskIds = [Uuid::v4()->toRfc4122()];
        $tagsData = [
            ['name' => 'Urgent', 'colour' => '#FF0000']
        ];
        $testData = [
            'taskIds' => $invalidTaskIds,
            'tags' => $tagsData
        ];

        // Act
        $this->client->request('POST', '/api/task/assign-tags', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Some task ids not found', $response['error']);
    }

    public function testAddTagsToTasksForbiddenAccess(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 11']);

        $testData = [
            'taskIds' => [$task->getId()],
            'tags' => [['name' => 'Urgent', 'colour' => '#FF0000']]
        ];

        // Act
        $this->client->request('POST', '/api/task/assign-tags', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testAddTagsToTasksMissingTagNameOrColour(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $testData = [
            'taskIds' => [$task->getId()],
            'tags' => [['name' => 'Urgent']]
        ];

        // Act
        $this->client->request('POST', '/api/task/assign-tags', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals('Each tag must have both name and colour', $response['error']);
    }

    public function testAddTagsToTasksInvalidTagValidation(): void
    {
        // Arrange
        $task = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $testData = [
            'taskIds' => [$task->getId()],
            'tags' => [['name' => 'InvalidTag', 'colour' => 'invalidColour']]
        ];

        // Act
        $this->client->request('POST', '/api/task/assign-tags', content: json_encode($testData));

        // Assert
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        $this->assertEquals('colour', $response[0]['field']);
        $this->assertEquals('Tag colour should match a hex colour value', $response[0]['message']);
    }

    public function testAddTagsToTasksDoesNotCreateExistingTag(): void
    {
        // Arrange
        $task1 = $this->taskRepository->findOneBy(['title' => 'Task 1']);
        $task2 = $this->taskRepository->findOneBy(['title' => 'Task 2']);
        $tasks = [$task1, $task2];
        $taskIds = array_map(fn($task) => $task->getId(), $tasks);

        $tagsData = [
            [
                'name' => 'Urgent',
                'colour' => '#FF0000'
            ]
        ];

        $testData = [
            'taskIds' => $taskIds,
            'tags' => $tagsData
        ];

        // Act
        $this->client->request('POST', '/api/task/assign-tags', content: json_encode($testData));
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(409);
        $this->assertArrayHasKey('error', $responseContent);
        $this->assertEquals("A tag with this name already exists: {$tagsData[0]['name']}", $responseContent['error']);
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

        $testData = [
            'taskIds' => $taskIds,
            'tagIds' => $tagIds,
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($testData));

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

        $testData = [
            'taskIds' => $invalidTaskIds,
            'tagIds' => $tagIds,
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($testData));

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
        $testData = [
            'taskIds' => [$task->getId()],
            'tagIds' => [$tag1->getId()],
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($testData));

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
        $testData = [
            'taskIds' => [$task->getId()],
            'tagIds' => [$invalidTagId],
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($testData));

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
        $testData = [
            'taskIds' => [$task->getId()],
            'tagIds' => [$tag->getId()],
        ];

        // Act
        $this->client->request('POST', '/api/task/remove-tags', content: json_encode($testData));

        // Assert
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Cannot modify tags created by another user', $response['error']);
    }
}
