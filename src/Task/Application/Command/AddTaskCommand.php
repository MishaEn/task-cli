<?php

namespace App\Task\Application\Command;

use App\Task\Domain\ValueObject\TaskDescription;

final readonly class AddTaskCommand
{
    public function __construct(
        public TaskDescription $description,
    )
    {
    }
}
