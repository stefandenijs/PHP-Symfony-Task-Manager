<?php

namespace App\Tests\Controller;

use App\Repository\TagRepositoryInterface;
use App\Repository\TaskRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

final class TagControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TagRepositoryInterface $tagRepository;
    private TaskRepositoryInterface $taskRepository;
    private SerializerInterface $serializer;

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->tagRepository = TaskControllerTest::getContainer()->get(TagRepositoryInterface::class);
        $this->taskRepository = TaskControllerTest::getContainer()->get(TaskRepositoryInterface::class);
        $this->serializer = TaskControllerTest::getContainer()->get(SerializerInterface::class);
        $this->client->request('POST', '/api/login', content: json_encode(['email' => 'bob@test.com', 'password' => 'testUser12345']));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $token = $data['token'];

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    public function testGetAllTags(): void
    {
        // Act
        $this->client->request('GET', '/api/tag');

        // Assert
        $this->assertResponseIsSuccessful();

        $tags = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($tags);
        $this->assertGreaterThan(0, count($tags));
        $this->assertArrayHasKey('name', $tags[0]);
        $this->assertArrayHasKey('colour', $tags[0]);
    }


    public function testGetTagById(): void
    {
        // Arrange
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 1']);

        // Act
        $this->client->request('GET', '/api/tag/' . $tag->getId());

        // Assert
        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($tag->getName(), $data['name']);
        $this->assertEquals($tag->getColour(), $data['colour']);
    }


    public function testGetTagByIdNotFound(): void
    {
        // Act
        $this->client->request('GET', '/api/tag/' . Uuid::v4()->toRfc4122());

        // Assert
        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Tag not found', $response['error']);
    }


    public function testGetTagByIdForbidden(): void
    {
        // Arrange
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 11']); // belongs to other user

        // Act
        $this->client->request('GET', '/api/tag/' . $tag->getId());

        // Assert
        $this->assertResponseStatusCodeSame(403);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }


    public function testCreateTag(): void
    {
        // Arrange
        $data = [
            'name' => 'New API Tag',
            'colour' => '#123456',
        ];

        // Act
        $this->client->jsonRequest('POST', '/api/tag', $data);

        // Assert
        $this->assertResponseStatusCodeSame(303);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/api/tag/', $location);
    }


    public function testCreateTagValidationFails(): void
    {
        // Arrange
        $data = [
            'name' => 'Invalid Tag',
            'colour' => 'not-a-colour'
        ];

        // Act
        $this->client->jsonRequest('POST', '/api/tag', $data);

        // Assert
        $this->assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('colour', $response[0]['field']);
        $this->assertStringContainsString('Tag colour should match a hex colour value', $response[0]['message']);
    }


    public function testDeleteTag(): void
    {
        // Arrange
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 2']); // from authenticated user

        // Act
        $this->client->request('DELETE', '/api/tag/' . $tag->getId());

        // Assert
        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Tag deleted successfully', $response['success']);
    }

    public function testDeleteTagForbidden(): void
    {
        // Arrange
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 12']); // another user’s tag

        // Act
        $this->client->request('DELETE', '/api/tag/' . $tag->getId());

        // Assert
        $this->assertResponseStatusCodeSame(403);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }


    public function testDeleteTagNotFound(): void
    {
        // Act
        $this->client->request('DELETE', '/api/tag/' . Uuid::v4()->toRfc4122());

        // Assert
        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Tag not found', $response['error']);
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
        assert($response['error'] === 'Task ids not found');
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
            'tags' => [['name' => 'Urgent', 'colour' => 'invalidColour']]  // Invalid colour format
        ];

        // Act
        $client->request('POST', '/api/task/assign-tags', content: json_encode($data));

        // Assert
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(400);
        assert($response[0]['field'] === 'colour');
        assert($response[0]['message'] === 'Tag colour should match a hex colour value');
    }
}
