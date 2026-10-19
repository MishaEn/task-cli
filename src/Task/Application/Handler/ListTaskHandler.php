<?php

declare(strict_types=1);

namespace App\Task\Application\Handler;

use App\Task\Application\Command\DeleteTaskCommand;
use App\Task\Application\Command\ListTaskCommand;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Repository\TaskRepositoryInterface;

final readonly class ListTaskHandler
{
    public function __construct(
        private readonly TaskRepositoryInterface $repository,
    ) {
    }

    public function __invoke(ListTaskCommand $command): array
    {
        return $this->repository->findByStatus($command->status);
    }
}
