<?php

declare(strict_types = 1);

namespace App\Task\Domain\Enum;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in-progress';
    case Done = 'done';
}
