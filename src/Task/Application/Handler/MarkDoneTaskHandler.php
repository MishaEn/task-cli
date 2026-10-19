<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\MarkDoneTaskCommand;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final readonly class MarkDoneTaskHandler
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
    ) {
    }

    public function __invoke(MarkDoneTaskCommand $command): void
    {
        $task = $this->repository->find($command->id);

        if ($task === null) {
            throw new \DomainException(sprintf(
                'Task with id %d not found.',
                $command->id->value(),
            ));
        }

        $task->markAsDone();
        $this->repository->save($task);
    }
}
