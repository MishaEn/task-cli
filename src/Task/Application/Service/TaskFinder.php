<?php

declare(strict_types=1);

namespace App\Task\Application\Service;

use App\Task\Application\Exception\TaskNotFoundException;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Domain\ValueObject\TaskId;

final readonly class TaskFinder
{
    public function __construct(
        private TaskRepositoryInterface $repository,
    ) {
    }

    public function get(TaskId $id): Task
    {
        return $this->repository->find($id) ?? throw TaskNotFoundException::withId($id);
    }
}
