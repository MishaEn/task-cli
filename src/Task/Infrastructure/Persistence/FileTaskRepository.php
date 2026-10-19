<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Domain\ValueObject\TaskDescription;
use App\Task\Domain\ValueObject\TaskId;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AsAlias(TaskRepositoryInterface::class)]
final class FileTaskRepository implements TaskRepositoryInterface
{
    /**
     * @var array<int, Task>
     */
    private array $tasks = [];

    private bool $loaded = false;

    public function __construct(
        #[Autowire('%tasks.file%')]
        private readonly string $filePath,
    ) {
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

    /**
     * @return list<Task>
     */
    public function findByStatus(TaskStatus $status): array
    {
        $this->load();

        return array_values(array_filter(
            $this->tasks,
            static fn (Task $task): bool => $task->status() === $status,
        ));
    }

    /**
     * @return list<Task>
     */
    public function findAll(): array
    {
        $this->load();

        return array_values($this->tasks);
    }

    public function delete(TaskId $id): bool
    {
        $this->load();

        if (!isset($this->tasks[$id->value()])) {
            return false;
        }

        unset($this->tasks[$id->value()]);
        $this->flush();

        return true;
    }

    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->tasks = [];

        if (!is_file($this->filePath)) {
            $this->loaded = true;

            return;
        }

        $content = file_get_contents($this->filePath);

        if ($content === false) {
            throw new RuntimeException(sprintf('Cannot read from file: %s', $this->filePath));
        }

        if (trim($content) === '') {
            $this->loaded = true;

            return;
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException(
                sprintf('The task file is corrupted: %s', $e->getMessage()),
                0,
                $e,
            );
        }

        if (!is_array($data) || !array_is_list($data)) {
            throw new RuntimeException('A JSON array of tasks was expected.');
        }

        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $task = $this->hydrate($row);
            $this->tasks[$task->id()->value()] = $task;
        }

        $this->loaded = true;
    }

    private function flush(): void
    {
        $directory = dirname($this->filePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Failed to create directory: %s', $directory));
        }

        $rows = array_map(
            fn (Task $task): array => $this->dehydrate($task),
            array_values($this->tasks),
        );

        $json = json_encode(
            $rows,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        $tmpPath = $this->filePath . '.tmp';

        if (file_put_contents($tmpPath, $json, LOCK_EX) === false) {
            throw new RuntimeException(sprintf('Failed to write temporary file: %s', $tmpPath));
        }

        if (!rename($tmpPath, $this->filePath)) {
            throw new RuntimeException(sprintf('Failed to save the tasks file: %s', $this->filePath));
        }
    }

    /**
     * @return array{id: int, description: string, status: string, created_at: string, updated_at: string}
     */
    private function dehydrate(Task $task): array
    {
        return [
            'id' => $task->id()->value(),
            'description' => $task->description()->value(),
            'status' => $task->status()->value,
            'created_at' => $task->createdAt()->format(DateTimeInterface::ATOM),
            'updated_at' => $task->updatedAt()->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Task
    {
        try {
            return Task::reconstitute(
                TaskId::fromString((string) ($row['id'] ?? '')),
                TaskDescription::fromString((string) ($row['description'] ?? '')),
                TaskStatus::from((string) ($row['status'] ?? '')),
                $this->parseDate($row['created_at'] ?? null, 'created_at'),
                $this->parseDate($row['updated_at'] ?? null, 'updated_at'),
            );
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf('Invalid task record in %s: %s', $this->filePath, $e->getMessage()),
                0,
                $e,
            );
        }
    }

    private function parseDate(mixed $value, string $field): DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException(sprintf('Task field "%s" must contain a date.', $field));
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception $e) {
            throw new RuntimeException(
                sprintf('Task field "%s" contains an invalid date.', $field),
                0,
                $e,
            );
        }
    }
}
