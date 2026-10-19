<?php

namespace App\Task\Application\Command;

use App\Task\Domain\ValueObject\TaskId;

final readonly class DeleteTaskCommand
{
    public function __construct(
        public TaskId $id
    )
    {
    }
}
