<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use DateTimeImmutable;
use JsonException;
use RuntimeException;

class FileTaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly string $filePath,
        private ?array $tasks = null,
    )
    {
    }

    public function save(Task $task): void
    {
        $this->load();
        $this->tasks[$task->id()->value()] = $task;
        $this->flush();
    }

    public function find(TaskId $id): ?Task
    {
        $this->load();
        return $this->tasks[$id->value()] ?? null;
    }

    public function findByStatus(TaskStatus $status): array
    {
        $this->load();

        return array_values(array_filter(
            $this->tasks,
            fn($task) => $task->status() === $status
        ));
    }

    /** @return Task[] */
    public function findAll(): array
    {
        $this->load();
        return array_values($this->tasks);
    }

    public function delete(TaskId $id): void
    {
        $this->load();
        unset($this->tasks[$id->value()]);
        $this->flush();
    }

    public function nextId(): TaskId
    {
        $this->load();

        if ($this->tasks === null) {
            return TaskId::first();
        }

        $maxId = max(array_map(
            fn (Task $task) => $task->id()->value(),
            $this->tasks
        ));

        return new TaskId($maxId)->next();
    }

    private function load(): void
    {
        if ($this->tasks !== null) {
            return;
        }

        if (!is_file($this->filePath)) {
            $this->tasks = null;
            return;
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new RuntimeException(
                sprintf('Cannot read from file: %s', $this->filePath)
            );
        }

        if (trim($content) === '') {
            $this->tasks = null;
            return;
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(
                sprintf('The task file is corrupted: %s', $e->getMessage()),
                0,
                $e
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException('A JSON array of tasks was expected.');
        }

        if ($data === []) {
            return;
        }

        $this->tasks = [];

        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $task = $this->hydrate($row);
            $this->tasks[$task->id()->value()] = $task;
        }
    }

    private function flush(): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(
                sprintf('Failed to create directory: %s', $directory)
            );
        }

        $rows = array_map(
            fn (Task $task) => $this->dehydrate($task),
            array_values($this->tasks)
        );

        $json = json_encode(
            $rows,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $tmpPath = $this->filePath . '.tmp';

        if (file_put_contents($tmpPath, $json) === false) {
            throw new RuntimeException(
                sprintf('Failed to write temporary file: %s', $tmpPath)
            );
        }

        if (!rename($tmpPath, $this->filePath)) {
            throw new RuntimeException(
                sprintf('Failed to save the tasks file: %s', $this->filePath)
            );
        }
    }

    private function dehydrate(Task $task): array
    {
        return [
            'id' => $task->id()->value(),
            'description' => $task->description()->value(),
            'status' => $task->status()->value,
            'created_at' => $task->createdAt()->format(\DateTimeInterface::ATOM),
            'updated_at' => $task->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function hydrate(array $row): Task
    {
        return new Task(
            TaskId::fromString((string) $row['id']),
            TaskDescription::fromString((string) $row['description']),
            TaskStatus::from((string) $row['status']),
            new DateTimeImmutable((string) $row['created_at']),
            new DateTimeImmutable((string) $row['updated_at']),
        );
    }
}
