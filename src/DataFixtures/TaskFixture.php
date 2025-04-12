<?php

namespace App\DataFixtures;

use App\Entity\Task;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TaskFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $task = new Task();
        $task->setTitle('Task 1');
        $task->setDescription('Task 1 description');
        $manager->persist($task);

        $manager->flush();
    }
}
