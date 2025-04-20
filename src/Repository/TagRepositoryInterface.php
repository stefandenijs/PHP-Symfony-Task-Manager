<?php

namespace App\Repository;

use App\Entity\Tag;

interface TagRepositoryInterface
{
    public function createOrUpdate(Tag $tag): void;
    public function createOrUpdateMany(array $tags): void;
    public function delete(Tag $tag): void;
}