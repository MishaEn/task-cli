<?php

namespace App\Task\Application\Command;

use App\Task\Domain\ValueObject\TaskId;

final readonly class UpdateTaskCommand
{
    public function __construct(
        public TaskId $id,
        public string $description,
    )
    {
    }
}
