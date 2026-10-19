<?php

namespace App\Task\Application\Handler;

use App\Task\Application\Command\DeleteTaskCommand;
use App\Task\Application\Command\UpdateTaskCommand;
use App\Task\Domain\Repository\TaskRepositoryInterface;

class UpdateTaskHandler
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateTaskCommand $command): void
    {
        $task = $this->repository->find($command->id);

        if ($task === null) {
            throw new \DomainException(sprintf(
                'Task with id %d not found.',
                $command->id->value(),
            ));
        }

        $task->updateDescription($command->description);
        $this->repository->save($task);
    }
}
