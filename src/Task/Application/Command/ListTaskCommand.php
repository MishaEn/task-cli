<?php

declare(strict_types=1);

namespace App\Task\Application\Command;

use App\Task\Domain\Enum\TaskStatus;

final readonly class ListTaskCommand
{
    public function __construct(
        public ?TaskStatus $status = null,
    )
    {
    }
}
