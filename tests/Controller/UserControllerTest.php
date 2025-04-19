<?php

namespace App\Tests\Controller;

use App\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepositoryInterface $userRepository;

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->userRepository = TaskControllerTest::getContainer()->get(UserRepositoryInterface::class);
        $this->client->request('POST', '/api/login', content: json_encode(['email' => 'test@test.com', 'password' => 'testUser12345']));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $token = $data['token'];

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    public function testGetUser(): void
    {
        // Arrange
        $client = $this->client;
        $id = 1;

        // Act
        $client->request('GET', "/api/user/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('username', $response);
        assert($response['id'] === 1);
        assert($response['email'] === 'test@test.com');
        assert($response['username'] === 'testUser');
    }

    public function testGetUserNotFound(): void
    {
        // Arrange
        $client = $this->client;
        $id = 999;

        // Act
        $client->request('GET', "/api/user/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertArrayHasKey('error', $response);
        assert($response['error'] === 'User not found');
    }

    public function testEditUser(): void
    {
        // Arrange
        $client = $this->client;
        $userRepository = $this->userRepository;
        $id = 1;

        $userData = [
            'username' => 'changedTestUser',
        ];

        // Act
        $client->request('PUT', "/api/user/$id", content: json_encode($userData));

        // Assert
        $this->assertResponseRedirects('/api/user/1');
        $this->assertResponseStatusCodeSame(303);
        $updatedUserRes = $userRepository->findOneBy(['username' => 'changedTestUser']);
        $this->assertEquals(1, $updatedUserRes->getId());
        $this->assertEquals('test@test.com', $updatedUserRes->getEmail());
        $this->assertEquals('changedTestUser', $updatedUserRes->getProfileUsername());
    }

    public function testEditUserNotFound(): void
    {
        // Arrange
        $client = $this->client;
        $id = 999;

        $userData = [
            'username' => 'changedTestUser',
        ];

        // Act
        $client->request('PUT', "/api/user/$id", content: json_encode($userData));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(404);
        $this->assertArrayHasKey('error', $response);
        assert($response['error'] === 'User not found');
    }

    public function testEditUserUnauthorized(): void
    {
        // Arrange
        $client = $this->client;
        $id = 2;

        $userData = [
            'username' => 'changedTestUser',
        ];


        // Act
        $client->request('PUT', "/api/user/$id", content: json_encode($userData));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(403);
        $this->assertArrayHasKey('error', $response);
        assert($response['error'] === 'Forbidden to access this resource');
    }

}
