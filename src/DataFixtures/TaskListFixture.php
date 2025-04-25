<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\TaskList;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TaskListFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $taskList = new TaskList();
            $taskList->setName('List ' . $i);
            $taskList->setOwner($this->getReference(UserFixture::TEST_USER, User::class));
            $manager->persist($taskList);
            for ($j = 1; $j <= 5; $j++) {
                $task = new Task();
                $task->setTitle('List ' . $i . ' task ' . $j);
                $task->setDescription('Task description ' . $j);
                $task->setOwner($this->getReference(UserFixture::TEST_USER, User::class));
                $manager->persist($task);
                $taskList->addTask($task);
            }
        }

        for ($i = 4; $i <= 6; $i++) {
            $taskList = new TaskList();
            $taskList->setName('List ' . $i);
            $taskList->setOwner($this->getReference(UserFixture::TEST_USER2, User::class));
            $manager->persist($taskList);
            for ($j = 1; $j <= 5; $j++) {
                $task = new Task();
                $task->setTitle('List ' . $i . ' task ' . $j);
                $task->setDescription('Task description ' . $j);
                $task->setOwner($this->getReference(UserFixture::TEST_USER2, User::class));
                $manager->persist($task);
                $taskList->addTask($task);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
