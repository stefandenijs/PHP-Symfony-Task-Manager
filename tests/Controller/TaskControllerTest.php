<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TaskControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = TaskControllerTest::createClient();
        $client->request('GET', '/task');
        self::assertResponseIsSuccessful();
    }
}
