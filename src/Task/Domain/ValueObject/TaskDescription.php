<?php

declare(strict_types=1);

namespace App\Task\Domain\ValueObject;

use DomainException;

final readonly class TaskDescription
{
    private const int MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new DomainException('Task description cannot be empty.');
        }

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw new DomainException(sprintf(
                'Task description cannot exceed %d characters.',
                self::MAX_LENGTH,
            ));
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
