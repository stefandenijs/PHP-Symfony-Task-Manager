<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;


    public function setUp(): void
    {
        self::bootKernel();

        $this->userRepository = self::getContainer()->get(UserRepository::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testCreateUser(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail("test10@test.com");
        $user->setUsername("test10");
        $user->setPlainPassword("superTestPassword123456@#$");
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword()));
        $user->setRoles(["ROLE_USER"]);

        // Act
        $this->userRepository->create($user);
        $newUser = $this->userRepository->find($user->getId());

        // Assert
        $this->assertEquals($user->getId(), $newUser->getId());
        $this->assertEquals($user->getEmail(), $newUser->getEmail());
        $this->assertEquals($user->getUsername(), $newUser->getUsername());
        $this->assertEquals($user->getRoles(), $newUser->getRoles());
    }
}
