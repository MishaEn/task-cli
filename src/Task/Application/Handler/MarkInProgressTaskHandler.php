<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\MarkInProgressTaskCommand;
use App\Task\Application\Lock\TaskStorageLockInterface;
use App\Task\Application\Service\TaskFinder;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class MarkInProgressTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TaskFinder $finder,
        private TaskStorageLockInterface $lock,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(MarkInProgressTaskCommand $command): void
    {
        $this->lock->synchronized(function () use ($command): void {
            $task = $this->finder->get($command->id);
            $task->startProgress($this->clock->now());
            $this->repository->save($task);
        });
    }
}
