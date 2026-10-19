<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\AddTaskCommand;
use App\Task\Application\Lock\TaskStorageLockInterface;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Domain\Service\TaskIdGeneratorInterface;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use Psr\Clock\ClockInterface;

final readonly class AddTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TaskIdGeneratorInterface $idGenerator,
        private TaskStorageLockInterface $lock,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(AddTaskCommand $command): TaskId
    {
        return $this->lock->synchronized(function () use ($command): TaskId {
            $task = Task::create(
                $this->idGenerator->next(),
                new TaskDescription($command->description),
                $this->clock->now(),
            );

            $this->repository->save($task);

            return $task->id();
        });
    }
}
