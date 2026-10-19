<?php

declare(strict_types = 1);

namespace App\Task\Domain\Entity;

use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use DateTimeImmutable;

final class Task
{
    public function __construct(
        private readonly TaskId $id,
        private TaskDescription $description,
        private TaskStatus $status,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    )
    {
        $this->assertDatesAreConsistent($createdAt, $updatedAt);
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

    public function updateDescription(string $description): void
    {
        $trimmed = trim($description);

        if ($trimmed === $this->description) {
            return;
        }

        $this->description = new TaskDescription($trimmed);
        $this->touch();
    }

    public function startProgress(): void
    {
        if ($this->status !== TaskStatus::Todo) {
            throw new \DomainException(sprintf(
                'Cannot start task in status "%s". Only "todo" can be started.',
                $this->status->value,
            ));
        }

        $this->status = TaskStatus::InProgress;
        $this->touch();
    }

    public function markAsDone(): void
    {
        if ($this->status === TaskStatus::Done) {
            return;
        }

        if ($this->status === TaskStatus::InProgress || $this->status === TaskStatus::Todo) {
            $this->status = TaskStatus::Done;
            $this->touch();
            return;
        }

        throw new \DomainException(sprintf(
            'Cannot complete task in status "%s".',
            $this->status->value,
        ));
    }

    public static function add(TaskId $id, TaskDescription $description, TaskStatus $status, DateTimeImmutable $createdAt, DateTimeImmutable $updatedAt): self
    {
        return new self($id, $description, $status, $createdAt, $updatedAt);
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    private function assertDatesAreConsistent(
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): void {
        if ($updatedAt < $createdAt) {
            throw new \DomainException('Updated date cannot be earlier than created date.');
        }
    }
}
