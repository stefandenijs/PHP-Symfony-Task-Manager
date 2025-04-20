<?php

namespace App\DataFixtures;

use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TagFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $tag = new Tag();
            $tag->setName('Tag ' . $i);
            $tag->setColour('#52eb34');
            $tag->setCreator($this->getReference(UserFixture::TEST_USER, User::class));
            $manager->persist($tag);
        }

        for ($i = 11; $i <= 20; $i++) {
            $tag = new Tag();
            $tag->setName('Tag ' . $i);
            $tag->setColour('#52eb34');
            $tag->setCreator($this->getReference(UserFixture::TEST_USER2, User::class));
            $manager->persist($tag);
        }
    }

    public function getDependencies(): array
    {
        return [
            UserFixture::class,
        ];
    }
}
