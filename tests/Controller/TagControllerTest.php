<?php

namespace App\Tests\Controller;

use App\Repository\TagRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
        $testData = [
            'name' => 'New API Tag',
            'colour' => '#123456',
        ];

        // Act
        $this->client->request('POST', '/api/tag', content: json_encode($testData));

        // Assert
        $this->assertResponseStatusCodeSame(303);

        $location = $this->client->getResponse()->headers->get('Location');
        $this->assertStringContainsString('/api/tag/', $location);
    }


    public function testCreateTagValidationFails(): void
    {
        // Arrange
        $testData =
            [
                'name' => 'Invalid Tag',
                'colour' => 'not-a-colour'
            ];

        // Act
        $this->client->request('POST', '/api/tag', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);

        $this->assertEquals('colour', $response[0]['field']);
        $this->assertEquals('Tag colour should match a hex colour value', $response[0]['message']);
    }

    public function testCreateTagAlreadyExistsForUser(): void
    {
        // Arrange
        $testData =
            [
                'name' => 'New API Tag',
                'colour' => '#123456',
            ];

        // Act
        $this->client->request('POST', '/api/tag', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Arrange
        $this->assertResponseStatusCodeSame(409);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('A tag with this name already exists', $response['error']);
    }

    public function testEditTag(): void
    {
        // Arrange
        $tagId = $this->tagRepository->findOneBy(['name' => 'Tag 7'])->getId();
        $testData = [
            'name' => 'Edited API Tag 7',
            'colour' => '#234567',
        ];

        // Act
        $this->client->request('PUT', "/api/tag/$tagId", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseRedirects("/api/tag/$tagId");
        $updatedTagRes = $this->tagRepository->findOneBy(['name' => 'Edited API Tag 7']);
        $this->assertNotNull($updatedTagRes);
        $this->assertEquals($testData['name'], $updatedTagRes->getName());
        $this->assertEquals($testData['colour'], $updatedTagRes->getColour());
    }

    public function testEditTagForbidden(): void
    {
        // Arrange
        $tagId = $this->tagRepository->findOneBy(['name' => 'Tag 12'])->getId();
        $testData = [
            'name' => 'Edited API Tag 12',
            'colour' => '#234567',
        ];

        // Act
        $this->client->request('PUT', "/api/tag/$tagId", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testEditTagAlreadyExistsForUser(): void
    {
        // Arrange
        $tagId = $this->tagRepository->findOneBy(['name' => 'Tag 8'])->getId();
        $testData = [
            'name' => 'Tag 6',
            'colour' => '#123456',
        ];

        // Act
        $this->client->request('PUT', "/api/tag/$tagId", content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(409);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('A tag with this name already exists', $response['error']);
    }

    public function testDeleteTag(): void
    {
        // Arrange
        $tagId = $this->tagRepository->findOneBy(['name' => 'Tag 2'])->getId();

        // Act
        $this->client->request('DELETE', "/api/tag/$tagId");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertEquals('Tag deleted successfully', $response['success']);
    }

    public function testDeleteTagForbidden(): void
    {
        // Arrange
        $tagId = $this->tagRepository->findOneBy(['name' => 'Tag 12'])->getId();

        // Act
        $this->client->request('DELETE', "/api/tag/$tagId");
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertEquals('Forbidden to access this resource', $response['error']);
    }

    public function testDeleteTagNotFound(): void
    {
        // Act
        $this->client->request('DELETE', '/api/tag/' . Uuid::v4()->toRfc4122());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertEquals('Tag not found', $response['error']);
    }
}
