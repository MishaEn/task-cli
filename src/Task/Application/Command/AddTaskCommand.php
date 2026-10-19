<?php

declare(strict_types=1);

namespace App\Task\Application\Command;

final readonly class AddTaskCommand
{
    public function __construct(
        public string $description,
    ) {
    }
}
