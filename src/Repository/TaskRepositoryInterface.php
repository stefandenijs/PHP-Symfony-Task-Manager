<?php

namespace App\Repository;

use App\Entity\Task;

interface TaskRepositoryInterface
{
    public function createOrUpdate(Task $task): void;
    public function delete(Task $task): void;
}