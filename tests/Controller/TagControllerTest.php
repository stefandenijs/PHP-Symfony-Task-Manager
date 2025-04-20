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

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->tagRepository = TaskControllerTest::getContainer()->get(TagRepositoryInterface::class);
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
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 11']);

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
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 2']);

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
        $tag = $this->tagRepository->findOneBy(['name' => 'Tag 12']);

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
}
