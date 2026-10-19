<?php

declare(strict_types=1);

namespace App\Task\Domain\Repository;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\ValueObject\TaskId;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;

    public function find(TaskId $id): ?Task;

    /**
     * @return list<Task>
     */
    public function findByStatus(TaskStatus $status): array;

    /**
     * @return list<Task>
     */
    public function findAll(): array;

    /**
     * @return bool true when the task existed and was removed, false otherwise
     */
    public function delete(TaskId $id): bool;
}
