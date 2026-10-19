<?php

declare(strict_types = 1);

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\ValueObject\TaskId;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;
    public function find(TaskId $id): ?Task;
    public function findByStatus(TaskStatus $status): array;
    public function findAll(): array;
    public function delete(TaskId $id): void;
    public function nextId(): TaskId;
}
