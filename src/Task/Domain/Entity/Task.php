<?php

declare(strict_types=1);

namespace App\Task\Domain\Entity;

use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use DateTimeImmutable;
use DomainException;

final class Task
{
    private function __construct(
        private readonly TaskId $id,
        private TaskDescription $description,
        private TaskStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        if ($updatedAt < $createdAt) {
            throw new DomainException('Updated date cannot be earlier than created date.');
        }
    }

    public static function create(
        TaskId $id,
        TaskDescription $description,
        DateTimeImmutable $now,
    ): self {
        return new self($id, $description, TaskStatus::Todo, $now, $now);
    }

    public static function reconstitute(
        TaskId $id,
        TaskDescription $description,
        TaskStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $description, $status, $createdAt, $updatedAt);
    }

    public function id(): TaskId
    {
        return $this->id;
    }

    public function description(): TaskDescription
    {
        return $this->description;
    }

    public function status(): TaskStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function updateDescription(TaskDescription $description, DateTimeImmutable $now): void
    {
        if ($description->equals($this->description)) {
            return;
        }

        $this->description = $description;
        $this->touch($now);
    }

    public function startProgress(DateTimeImmutable $now): void
    {
        if ($this->status !== TaskStatus::Todo) {
            throw new DomainException(sprintf(
                'Cannot start task in status "%s". Only "todo" can be started.',
                $this->status->value,
            ));
        }

        $this->status = TaskStatus::InProgress;
        $this->touch($now);
    }

    public function markAsDone(DateTimeImmutable $now): void
    {
        if ($this->status === TaskStatus::Done) {
            return;
        }

        $this->status = TaskStatus::Done;
        $this->touch($now);
    }

    private function touch(DateTimeImmutable $now): void
    {
        $this->updatedAt = max($this->updatedAt, $this->createdAt, $now);
    }
}
