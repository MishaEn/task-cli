<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use App\Task\Infrastructure\Persistence\FileTaskRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FileTaskRepositoryTest extends TestCase
{
    private string $directory;
    private string $filePath;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/task-cli-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->directory, 0775, true));
        $this->filePath = $this->directory . '/tasks.json';
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            @unlink($file);
        }

        @rmdir($this->directory);
    }

    public function testSaveAndFindTask(): void
    {
        $repository = $this->createRepository();
        $task = $this->createTask(1, 'Buy milk');

        $repository->save($task);

        $found = $repository->find(new TaskId(1));

        self::assertNotNull($found);
        self::assertSame('Buy milk', $found->description()->value());
        self::assertSame(TaskStatus::Todo, $found->status());
    }

    public function testFindAllOnMissingFileReturnsEmptyList(): void
    {
        self::assertSame([], $this->createRepository()->findAll());
    }

    public function testFindAllOnEmptyFileReturnsEmptyList(): void
    {
        file_put_contents($this->filePath, '');

        self::assertSame([], $this->createRepository()->findAll());
    }

    public function testFindAllOnEmptyJsonArrayReturnsEmptyList(): void
    {
        file_put_contents($this->filePath, '[]');

        self::assertSame([], $this->createRepository()->findAll());
    }

    public function testFindByStatusReturnsOnlyMatchingTasks(): void
    {
        $repository = $this->createRepository();
        $repository->save($this->createTask(1, 'Todo task'));
        $repository->save($this->createTask(2, 'Done task', TaskStatus::Done));

        $done = $repository->findByStatus(TaskStatus::Done);

        self::assertCount(1, $done);
        self::assertSame(2, $done[0]->id()->value());
    }

    public function testDeleteReportsWhetherTheTaskExisted(): void
    {
        $repository = $this->createRepository();
        $repository->save($this->createTask(1));

        self::assertTrue($repository->delete(new TaskId(1)));
        self::assertFalse($repository->delete(new TaskId(1)));
        self::assertNull($repository->find(new TaskId(1)));
    }

    public function testDeleteOfMissingTaskOnMissingFileReturnsFalse(): void
    {
        self::assertFalse($this->createRepository()->delete(new TaskId(999)));
    }

    public function testCorruptedJsonThrows(): void
    {
        file_put_contents($this->filePath, '{');

        $this->expectException(RuntimeException::class);

        $this->createRepository()->findAll();
    }

    public function testInvalidTaskRecordThrows(): void
    {
        file_put_contents($this->filePath, '[{"id": "abc", "description": "Task", "status": "todo"}]');

        $this->expectException(RuntimeException::class);

        $this->createRepository()->findAll();
    }

    public function testTaskIsPersistedForANewRepositoryInstance(): void
    {
        $this->createRepository()->save($this->createTask(1, 'Buy milk'));

        $found = $this->createRepository()->find(new TaskId(1));

        self::assertNotNull($found);
        self::assertSame('Buy milk', $found->description()->value());
    }

    private function createRepository(): FileTaskRepository
    {
        return new FileTaskRepository($this->filePath);
    }

    private function createTask(int $id, string $description = 'Task', TaskStatus $status = TaskStatus::Todo): Task
    {
        $now = new DateTimeImmutable('2026-01-01 10:00:00');

        return Task::reconstitute(
            new TaskId($id),
            new TaskDescription($description),
            $status,
            $now,
            $now,
        );
    }
}
