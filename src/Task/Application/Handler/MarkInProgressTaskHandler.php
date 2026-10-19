<?php

namespace App\Task\Application\Handler;

use App\Task\Application\Command\DeleteTaskCommand;
use App\Task\Application\Command\MarkInProgressTaskCommand;
use App\Task\Domain\Repository\TaskRepositoryInterface;

class MarkInProgressTaskHandler
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
    ) {
    }

    public function __invoke(MarkInProgressTaskCommand $command): void
    {
        $task = $this->repository->find($command->id);

        if ($task === null) {
            throw new \DomainException(sprintf(
                'Task with id %d not found.',
                $command->id->value(),
            ));
        }

        $task->startProgress();
        $this->repository->save($task);
    }
}
