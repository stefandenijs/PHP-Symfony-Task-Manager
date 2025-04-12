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

    public function testShow(): void
    {
        $client = TaskControllerTest::createClient();
        $crawler = $client->request('GET', '/task/{id}');
        $this->assertResponseIsSuccessful();
    }
}
