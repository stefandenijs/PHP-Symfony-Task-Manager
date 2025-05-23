<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[groups(['task_single', 'task'])]
    private ?Uuid $id = null;
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'A valid task title is required', groups: ['task'])]
    #[groups(['task_single', 'task', 'task:create'])]
    private ?string $title = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[groups(['task_single', 'task', 'task:create'])]
    private ?string $description = null;
    #[ORM\Column(type: Types::DATETIMETZ_MUTABLE, nullable: true)]
    #[Assert\Valid] // Only assert the type if not null.
        // TODO: Fix this at some point that it allows to include timezone data
    #[Assert\Type(Types::DATETIME_MUTABLE, message: 'A valid task deadline is required')]
    #[groups(['task_single', 'task'])]
    private ?DateTimeInterface $deadline = null;
    #[ORM\Column]
    private ?DateTimeImmutable $createdAt;
    #[ORM\Column(nullable: true)]
    #[Ignore]
    private ?DateTimeImmutable $updatedAt = null;
    #[ORM\Column]
    #[groups(['task_single', 'task'])]
    private ?bool $completed = false;
    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'subTasks')]
    #[MaxDepth(1)]
    private ?self $parent = null;
    /**
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[Groups(['task'])]
    private Collection $subTasks;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'tasks')]
    #[Groups(['task'])]
    private Collection $tags;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?TaskList $taskList = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable('now');
        $this->subTasks = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDeadline(): ?DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?DateTimeInterface $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): static
    {
        $this->completed = $completed;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    #[Groups(['task_owner'])]
    public function getOwnerId(): ?Uuid
    {
        return $this->owner->getId();
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSubTasks(): Collection
    {
        return $this->subTasks;
    }

    public function addSubTask(self $subTask): static
    {
        if (!$this->subTasks->contains($subTask)) {
            $this->subTasks->add($subTask);
            $subTask->setParent($this);
        }

        return $this;
    }

    public function removeSubTask(self $subTask): static
    {
        if ($this->subTasks->removeElement($subTask)) {
            // set the owning side to null (unless already changed)
            if ($subTask->getParent() === $this) {
                $subTask->setParent(null);
            }
        }

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    #[Groups(['task_parent'])]
    public function getParentId(): ?Uuid
    {
        return $this->parent?->getId();
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getTaskList(): ?TaskList
    {
        return $this->taskList;
    }

    public function setTaskList(?TaskList $taskList): static
    {
        $this->taskList = $taskList;

        return $this;
    }
}
