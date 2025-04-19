<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


// Could technically be implemented in one fixture class for ease.
class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public const TEST_USER = 'test-user';
    public const TEST_USER2 = 'test-user2';
    public function __construct(UserPasswordHasherInterface $passwordHasher) {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setUsername('testUser');
        $user->setPlainPassword('testUser12345');
        $user->setEmail('test@test.com');
        $user->setRoles(['ROLE_USER']);

        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword()));
        $manager->persist($user);

        $otherUser = new User();
        $otherUser->setUsername('testUser');
        $otherUser->setPlainPassword('testUser12345');
        $otherUser->setEmail('testUser2@test.com');
        $otherUser->setRoles(['ROLE_USER']);

        $otherUser->setPassword($this->passwordHasher->hashPassword($otherUser, $otherUser->getPlainPassword()));
        $manager->persist($otherUser);
        $manager->flush();

        $this->addReference(self::TEST_USER, $user);
        $this->addReference(self::TEST_USER2, $otherUser);
    }
}
