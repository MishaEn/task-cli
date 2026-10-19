<?php

namespace App\Task\Application\Command;

use App\Task\Domain\ValueObject\TaskId;

final readonly class MarkDoneTaskCommand
{
    public function __construct(
        public TaskId $id
    )
    {
    }
}
