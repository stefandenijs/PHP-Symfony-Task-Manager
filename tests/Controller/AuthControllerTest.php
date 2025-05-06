<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function SetUp(): void
    {
        $this->client = AuthControllerTest::createClient();
    }

    public function testRegister(): void
    {
        // Arrange
        $testData = ['email' => 'frank@test.com', 'password' => 'testUser12345@#!', 'username' => 'Frank'];

        // Act
        $this->client->request('POST', '/api/register', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(201);
        assert($response['message'] === 'User successfully registered');
    }

    public function testRegisterWithInvalidEmail(): void
    {
        // Arrange
        $testData = ['email' => 'franktest.com', 'password' => 'testUser12345@#!', 'username' => 'The other Frank'];

        // Act
        $this->client->request('POST', '/api/register', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert;
        $statusCode = $this->client->getResponse()->getStatusCode();
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
        $testData = ['password' => 'testUser12345@#!', 'username' => 'testUser2'];

        // Act
        $this->client->request('POST', '/api/register', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

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
        $testData = ['email' => 'dave@test.com', 'password' => 'testUser!', 'username' => 'testUser2'];

        // Act
        $this->client->request('POST', '/api/register', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

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
        $testData = ['email' => 'dave@test.com', 'username' => 'testUser3'];

        // Act
        $this->client->request('POST', '/api/register', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

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
        $testData = ['email' => 'dave@test.com', 'password' => 'testUser12345@#!'];

        // Act
        $this->client->request('POST', '/api/register', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

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
        $testData = ['email' => 'frank@test.com', 'password' => 'testUser12345@#!'];

        // Act
        $this->client->request('POST', '/api/login', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

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
        $testData = ['email' => 'frank@test.com', 'password' => 'tesUser12345@#!'];

        // Act
        $this->client->request('POST', '/api/login', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

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
        $testData = ['password' => 'tesUser12345@#!'];

        // Act
        $this->client->request('POST', '/api/login', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

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
        $testData = ['email' => 'frank@test.com'];

        // Act
        $this->client->request('POST', '/api/login', content: json_encode($testData));
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Assert
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey("error", $response);
        $this->assertArrayHasKey("message", $response);
        assert($response['error'] === 'password');
        assert($response['message'] === 'Password is required');
    }

}
