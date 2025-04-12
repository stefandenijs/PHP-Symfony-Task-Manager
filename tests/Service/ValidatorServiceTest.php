<?php

namespace App\Tests\Service;

use App\Entity\Task;
use App\Service\ValidatorService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ValidatorServiceTest extends KernelTestCase
{
    private ValidatorService $validator;
    public function setUp(): void
    {
        self::bootKernel();

        $this->validator = self::getContainer()->get(ValidatorService::class);
    }

    public function testValidateInvalidTask(): void
    {
        // Arrange
        $invalidTask = new Task();
        $invalidTask->setDescription("Create Task");

        // Act
        $result = $this->validator->validate($invalidTask, null, ["task"]);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(400, $result->getStatusCode());
        $decodedResult = json_decode($result->getContent(), true);
        $this->assertArrayHasKey("field", $decodedResult);
        $this->assertArrayHasKey("message", $decodedResult);
        $this->assertArrayHasKey("code", $decodedResult);
        $this->assertStringContainsString("title", $decodedResult["field"]);
        $this->assertStringContainsString("A valid task title is required", $decodedResult["message"]);
    }
}
