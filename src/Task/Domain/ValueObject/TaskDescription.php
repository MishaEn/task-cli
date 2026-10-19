<?php

declare(ticks = 1);

namespace App\Task\Domain\ValueObject;

final readonly class TaskDescription
{
    public function __construct(
        private string $value
    )
    {
        if ($value === '') {
            throw new \DomainException('Task description cannot be empty.');
        }

        if (mb_strlen($value) > 255) {
            throw new \DomainException('Task description cannot exceed 255 characters.');
        }
    }

    public function __toString(): string
    {
        return $this->value();
    }

    public function value(): string
    {
        return $this->value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
