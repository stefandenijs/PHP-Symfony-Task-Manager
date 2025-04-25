<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TaskFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $task = new Task();
            $task->setTitle('Task ' . $i);
            $task->setDescription('Task description ' . $i);
            $task->setOwner($this->getReference(UserFixture::TEST_USER, User::class));
            $manager->persist($task);
            for ($j = 1; $j <= 5; $j++) {
                $subTask = new Task();
                $subTask->setTitle('SubTask ' . $j);
                $subTask->setDescription('SubTask description ' . $j);
                $subTask->setOwner($this->getReference(UserFixture::TEST_USER, User::class));
                $manager->persist($subTask);
                $task->addSubTask($subTask);
            }
        }

        for ($i = 11; $i <= 20; $i++) {
            $task = new Task();
            $task->setTitle('Task ' . $i);
            $task->setDescription('Task description ' . $i);
            $task->setOwner($this->getReference(UserFixture::TEST_USER2, User::class));
            $manager->persist($task);
            for ($j = 1; $j <= 5; $j++) {
                $subTask = new Task();
                $subTask->setTitle('SubTask ' . $j);
                $subTask->setDescription('SubTask description ' . $j);
                $subTask->setOwner($this->getReference(UserFixture::TEST_USER2, User::class));
                $manager->persist($subTask);
                $task->addSubTask($subTask);
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
