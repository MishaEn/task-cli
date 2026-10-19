<?php

declare(strict_types=1);

namespace App\Task\Application\Exception;

use App\Task\Domain\ValueObject\TaskId;
use RuntimeException;

final class TaskNotFoundException extends RuntimeException
{
    public static function withId(TaskId $id): self
    {
        return new self(sprintf('Task with id %d not found.', $id->value()));
    }
}
