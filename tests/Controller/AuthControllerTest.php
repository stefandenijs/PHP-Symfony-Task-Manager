<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    protected function setUpClient(): KernelBrowser
    {
        return AuthControllerTest::createClient();
    }

    public function testRegister(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/register', content: json_encode(['email' => 'frank@test.com', 'password' => 'testUser12345@#!', 'username' => 'Frank']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(201);
        assert($response['message'] === 'User successfully registered');
    }

    public function testRegisterWithInvalidEmail(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/register', content: json_encode(['email' => 'franktest.com', 'password' => 'testUser12345@#!', 'username' => 'The other Frank']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert;
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [400, 422]));
        $this->assertArrayHasKey("field", $response[0]);
        $this->assertArrayHasKey("message", $response[0]);
        $this->assertArrayHasKey("code", $response[0]);
        assert($response[0]['field'] === 'email');
        assert($response[0]['message'] === 'Invalid email address');
    }

    public function testRegisterWithMissingEmail(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/register', content: json_encode(['password' => 'testUser12345@#!', 'username' => 'testUser2']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey("field", $response[0]);
        $this->assertArrayHasKey("message", $response[0]);
        $this->assertArrayHasKey("code", $response[0]);
        assert($response[0]['field'] === 'email');
        assert($response[0]['message'] === 'Email address is required');
    }

    public function testRegisterWithInvalidPassword(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/register', content: json_encode(['email' => 'dave@test.com', 'password' => 'testUser!', 'username' => 'testUser2']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey("field", $response[0]);
        $this->assertArrayHasKey("message", $response[0]);
        $this->assertArrayHasKey("code", $response[0]);
        assert($response[0]['field'] === 'plainPassword');
        assert($response[0]['message'] === 'The password strength is too low. Please use a stronger password.');
    }

    public function testRegisterWithMissingPassword(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/register', content: json_encode(['email' => 'dave@test.com', 'username' => 'testUser3']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey("field", $response[0]);
        $this->assertArrayHasKey("message", $response[0]);
        $this->assertArrayHasKey("code", $response[0]);
        assert($response[0]['field'] === 'plainPassword');
        assert($response[0]['message'] === 'Password is required');
    }

    public function testRegisterWithMissingUsername(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/register', content: json_encode(['email' => 'dave@test.com', 'password' => 'testUser12345@#!']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey("field", $response[0]);
        $this->assertArrayHasKey("message", $response[0]);
        $this->assertArrayHasKey("code", $response[0]);
        assert($response[0]['field'] === 'username');
        assert($response[0]['message'] === 'Username is required');
    }

    public function testLogin(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/login', content: json_encode(['email' => 'frank@test.com', 'password' => 'testUser12345@#!']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(200);
        $this->assertArrayHasKey("token", $response);
        $this->assertArrayHasKey("message", $response);
        $this->assertArrayHasKey("user", $response);
        $this->assertArrayHasKey("username", $response["user"]);
        $this->assertArrayHasKey("email", $response["user"]);
    }

    public function testLoginInvalidCredentials(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/login', content: json_encode(['email' => 'frank@test.com', 'password' => 'tesUser12345@#!']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(401);
        $this->assertArrayHasKey("error", $response);
        $this->assertArrayHasKey("message", $response);
        assert($response['error'] === 'authentication');
        assert($response['message'] === 'Invalid credentials');
    }

    public function testLoginMissingEmail(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/login', content: json_encode(['password' => 'tesUser12345@#!']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey("error", $response);
        $this->assertArrayHasKey("message", $response);
        assert($response['error'] === 'email');
        assert($response['message'] === 'Email is required');
    }


    public function testLoginMissingPassword(): void
    {
        // Arrange
        $client = $this->setUpClient();

        // Act
        $client->request('POST', '/api/login', content: json_encode(['email' => 'frank@test.com']));
        $response = json_decode($client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey("error", $response);
        $this->assertArrayHasKey("message", $response);
        assert($response['error'] === 'password');
        assert($response['message'] === 'Password is required');
    }

}
