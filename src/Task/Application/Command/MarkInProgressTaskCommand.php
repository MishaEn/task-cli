<?php

namespace App\Task\Application\Command;

use App\Task\Domain\ValueObject\TaskId;

final readonly class MarkInProgressTaskCommand
{
    public function __construct(
        public TaskId $id
    )
    {
    }
}
