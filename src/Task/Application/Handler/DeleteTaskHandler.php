<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\DeleteTaskCommand;
use App\Task\Application\Exception\TaskNotFoundException;
use App\Task\Application\Lock\TaskStorageLockInterface;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final readonly class DeleteTaskHandler
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        private TaskStorageLockInterface $lock,
    ) {
    }

    public function __invoke(DeleteTaskCommand $command): void
    {
        $deleted = $this->lock->synchronized(
            fn (): bool => $this->repository->delete($command->id),
        );

        if (!$deleted) {
            throw TaskNotFoundException::withId($command->id);
        }
    }
}
