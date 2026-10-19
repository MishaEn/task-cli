<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\UpdateTaskCommand;
use App\Task\Application\Lock\TaskStorageLockInterface;
use App\Task\Application\Service\TaskFinder;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Domain\ValueObject\TaskDescription;
use Psr\Clock\ClockInterface;

final readonly class UpdateTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TaskFinder $finder,
        private TaskStorageLockInterface $lock,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(UpdateTaskCommand $command): void
    {
        $this->lock->synchronized(function () use ($command): void {
            $task = $this->finder->get($command->id);
            $task->updateDescription(new TaskDescription($command->description), $this->clock->now());
            $this->repository->save($task);
        });
    }
}
