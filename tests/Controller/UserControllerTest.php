<?php

namespace App\Tests\Controller;

use App\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepositoryInterface $userRepository;

    public function setUp(): void
    {
        $this->client = TaskControllerTest::createClient();
        $this->userRepository = TaskControllerTest::getContainer()->get(UserRepositoryInterface::class);
        $this->client->request('POST', '/api/login', content: json_encode(['email' => 'bob@test.com', 'password' => 'testUser12345']));

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $token = $data['token'];

        $this->client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
    }

    public function testGetUser(): void
    {
        // Arrange
        $client = $this->client;
        $user = $this->userRepository->findOneBy(['email' => 'mike@test.com']);
        $id = $user->getId();

        // Act
        $client->request('GET', "/api/user/$id");
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('username', $response);
        assert($response['id'] === (string)$user->getId());
        assert($response['email'] === 'mike@test.com');
        assert($response['username'] === 'Mike');
    }

    public function testGetUserNotFound(): void
    {
        // Arrange
        $client = $this->client;
        $fakeUuid = Uuid::v4()->toRfc4122();

        // Act
        $client->request('GET', "/api/user/$fakeUuid");
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
        $user = $userRepository->findOneBy(['email' => 'bob@test.com']);

        $userData = [
            'username' => 'changedTestUser',
        ];

        // Act
        $client->request('PUT', "/api/user/", content: json_encode($userData));

        // Assert
        $this->assertResponseRedirects("/api/user/{$user->getId()}");
        $this->assertResponseStatusCodeSame(303);
        $updatedUserRes = $userRepository->findOneBy(['username' => 'changedTestUser']);
        $this->assertequals($user->getId(), $updatedUserRes->getId());
        $this->assertEquals('bob@test.com', $updatedUserRes->getEmail());
        $this->assertEquals('changedTestUser', $updatedUserRes->getProfileUsername());
    }
}
