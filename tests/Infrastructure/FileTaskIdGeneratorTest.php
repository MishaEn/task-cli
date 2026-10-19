<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use App\Task\Infrastructure\Persistence\FileTaskIdGenerator;
use App\Task\Infrastructure\Persistence\FileTaskRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FileTaskIdGeneratorTest extends TestCase
{
    private string $directory;
    private string $filePath;
    private FileTaskRepository $repository;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/task-cli-id-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->directory, 0775, true));
        $this->filePath = $this->directory . '/tasks.json';
        $this->repository = new FileTaskRepository($this->filePath);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
    }

    public function testItStartsFromOne(): void
    {
        self::assertSame(1, $this->createGenerator()->next()->value());
    }

    public function testItIncrementsAcrossGeneratorInstances(): void
    {
        self::assertSame(1, $this->createGenerator()->next()->value());
        self::assertSame(2, $this->createGenerator()->next()->value());
        self::assertSame(3, $this->createGenerator()->next()->value());
    }

    public function testItDoesNotReuseIdsAfterDeletingTheHighestTask(): void
    {
        $generator = $this->createGenerator();

        $firstId = $generator->next();
        $this->repository->save($this->createTask($firstId, 'First'));

        $secondId = $generator->next();
        $this->repository->save($this->createTask($secondId, 'Second'));

        self::assertTrue($this->repository->delete($secondId));

        self::assertSame(3, $generator->next()->value());
    }

    public function testItContinuesFromIdsAlreadyPresentInTheStorage(): void
    {
        $this->repository->save($this->createTask(new TaskId(5), 'Existing task'));

        self::assertSame(6, $this->createGenerator()->next()->value());
    }

    private function createGenerator(): FileTaskIdGenerator
    {
        return new FileTaskIdGenerator($this->repository, $this->filePath);
    }

    private function createTask(TaskId $id, string $description): Task
    {
        return Task::create(
            $id,
            new TaskDescription($description),
            new DateTimeImmutable('2026-01-01 10:00:00'),
        );
    }
}
