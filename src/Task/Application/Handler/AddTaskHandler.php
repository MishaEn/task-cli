<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\AddTaskCommand;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Domain\ValueObject\TaskId;

final readonly class AddTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
    ) {
    }

    public function __invoke(AddTaskCommand $command): TaskId
    {
        $task = Task::add(
            id: $this->repository->nextId(),
            description:  $command->description,
            status: TaskStatus::Todo,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->repository->save($task);

        return $task->id();
    }
}
