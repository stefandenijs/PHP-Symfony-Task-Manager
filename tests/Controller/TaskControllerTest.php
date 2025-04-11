<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class TaskControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = TaskControllerTest::createClient();

        $crawler = $client->request('GET', '/task');
        $this->assertResponseIsSuccessful();
    }
}
