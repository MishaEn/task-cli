<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use DomainException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class TaskTest extends TestCase
{
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-01-01 10:00:00');
    }

    public function testCreateStartsAsTodo(): void
    {
        $now = $this->clock->now();
        $task = Task::create(new TaskId(1), new TaskDescription('Task'), $now);

        self::assertSame(TaskStatus::Todo, $task->status());
        self::assertEquals($now, $task->createdAt());
        self::assertEquals($now, $task->updatedAt());
    }

    public function testUpdateDescriptionTouchesTheTask(): void
    {
        $task = $this->createTask();

        $this->clock->sleep(60);
        $task->updateDescription(new TaskDescription('New description'), $this->clock->now());

        self::assertSame('New description', $task->description()->value());
        self::assertEquals($this->clock->now(), $task->updatedAt());
    }

    public function testUpdateDescriptionWithTheSameValueDoesNotTouchTheTask(): void
    {
        $task = $this->createTask();
        $updatedAt = $task->updatedAt();

        $this->clock->sleep(60);
        $task->updateDescription(new TaskDescription(' Task '), $this->clock->now());

        self::assertEquals($updatedAt, $task->updatedAt());
    }

    public function testStartProgressMovesTodoToInProgress(): void
    {
        $task = $this->createTask();

        $this->clock->sleep(60);
        $task->startProgress($this->clock->now());

        self::assertSame(TaskStatus::InProgress, $task->status());
        self::assertEquals($this->clock->now(), $task->updatedAt());
    }

    public function testStartProgressTwiceIsRejected(): void
    {
        $task = $this->createTask();
        $task->startProgress($this->clock->now());

        $this->expectException(DomainException::class);

        $task->startProgress($this->clock->now());
    }

    public function testMarkAsDoneFromTodo(): void
    {
        $task = $this->createTask();

        $task->markAsDone($this->clock->now());

        self::assertSame(TaskStatus::Done, $task->status());
    }

    public function testMarkAsDoneIsIdempotent(): void
    {
        $task = $this->createTask();
        $task->markAsDone($this->clock->now());
        $updatedAt = $task->updatedAt();

        $this->clock->sleep(60);
        $task->markAsDone($this->clock->now());

        self::assertSame(TaskStatus::Done, $task->status());
        self::assertEquals($updatedAt, $task->updatedAt());
    }

    public function testReconstituteRejectsInconsistentDates(): void
    {
        $this->expectException(DomainException::class);

        Task::reconstitute(
            new TaskId(1),
            new TaskDescription('Task'),
            TaskStatus::Todo,
            new \DateTimeImmutable('2026-01-02 10:00:00'),
            new \DateTimeImmutable('2026-01-01 10:00:00'),
        );
    }

    private function createTask(): Task
    {
        return Task::create(new TaskId(1), new TaskDescription('Task'), $this->clock->now());
    }
}
