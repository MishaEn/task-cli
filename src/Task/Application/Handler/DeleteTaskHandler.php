<?php

namespace App\Task\Application\Handler;

use App\Task\Application\Command\DeleteTaskCommand;
use App\Task\Domain\Repository\TaskRepositoryInterface;


class DeleteTaskHandler
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
    ) {
    }

    public function __invoke(DeleteTaskCommand $command): void
    {
        $this->repository->delete($command->id);
    }
}
