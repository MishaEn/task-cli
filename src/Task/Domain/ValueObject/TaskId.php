<?php

declare(strict_types = 1);

namespace App\Task\Domain\ValueObject;

use DomainException;

final readonly class TaskId
{
    public function __construct(
        private int $value
    )
    {
        if ($value < 0) {
            throw new DomainException('Task id cannot be negative');
        }

        if ($value === 0) {
            throw new DomainException('Task id cannot be zero');
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(TaskId $other): bool
    {
        return $other->value === $this->value;
    }

    public static function fromString(string $value): self
    {
        if (!ctype_digit($value)) {
            throw new DomainException(sprintf('Task id must be a positive integer, got "%s"', $value));
        }

        return new self((int) $value);
    }

    public static function first(): self
    {
        return new self(1);
    }

    public function next(): self
    {
        return new self($this->value + 1);
    }
}
