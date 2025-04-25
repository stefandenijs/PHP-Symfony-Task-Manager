<?php

namespace App\Repository;

use App\Entity\TaskList;

interface TaskListRepositoryInterface
{
    public function createOrUpdate(TaskList $taskList): void;
    public function delete(TaskList $taskList): void;
}