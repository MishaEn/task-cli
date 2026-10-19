<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\DeleteTaskCommand;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final readonly class DeleteTaskHandler
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
