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

        $manager->flush();

        $this->addReference(self::TEST_USER, $user);
    }
}
