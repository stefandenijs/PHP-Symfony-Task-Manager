<?php

namespace App\Tests\Repository;

use App\Entity\Tag;
use App\Repository\TagRepositoryInterface;
use App\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TagRepositoryTest extends KernelTestCase
{
    private TagRepositoryInterface $tagRepository;
    private UserRepositoryInterface $userRepository;

    public function setUp(): void
    {
        self::bootKernel();

        $this->tagRepository = self::getContainer()->get(TagRepositoryInterface::class);
        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);

    }
    public function testCreate(): Tag
    {
        // Arrange
        $user = $this->userRepository->findOneByEmail("bob@test.com");
        $tag = new Tag();
        $tag->setName("Study");
        $tag->setColour("#4287f5");
        $tag->setCreator($user);

        // Act
        $this->tagRepository->createOrUpdate($tag);
        $newTag = $this->tagRepository->find($tag->getId());

        // Assert
        $this->assertnotnull($newTag);
        $this->assertEquals($tag, $newTag);
        assert($newTag->getName() === "Study");
        assert($newTag->getColour() === "#4287f5");

        return $newTag;
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(Tag $tag): Tag
    {
        // Arrange
        $tagToUpdate = $this->tagRepository->find($tag->getId());
        $tagToUpdate->setName("Work");
        $tagToUpdate->setColour("#42f56f");

        // Act
        $this->tagRepository->createOrUpdate($tagToUpdate);
        $updatedTag = $this->tagRepository->find($tagToUpdate->getId());

        // Assert
        $this->assertEquals($tagToUpdate, $updatedTag);
        assert($updatedTag->getName() === "Work");
        assert($tagToUpdate->getColour() === "#42f56f");

        return $updatedTag;
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(Tag $tag): void
    {
        $taskToDelete = $this->tagRepository->find($tag->getId());
        $this->tagRepository->delete($taskToDelete);

        $deletedTag = $this->tagRepository->find($tag->getId());

        $this->assertNull($deletedTag);
    }
}
