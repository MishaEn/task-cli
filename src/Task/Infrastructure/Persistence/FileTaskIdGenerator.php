<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Persistence;

use App\Task\Domain\Repository\TaskRepositoryInterface;
use App\Task\Domain\Service\TaskIdGeneratorInterface;
use App\Task\Domain\ValueObject\TaskId;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsAlias(TaskIdGeneratorInterface::class)]
final readonly class FileTaskIdGenerator implements TaskIdGeneratorInterface
{
    public function __construct(
        private TaskRepositoryInterface $repository,
        #[Autowire('%tasks.file%')]
        private string $tasksFilePath,
    ) {
    }

    public function next(): TaskId
    {
        $sequencePath = $this->tasksFilePath . '.seq';
        $directory = dirname($sequencePath);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Failed to create directory: %s', $directory));
        }

        $handle = fopen($sequencePath, 'c+b');

        if ($handle === false) {
            throw new RuntimeException(sprintf('Cannot open the id sequence file: %s', $sequencePath));
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException(sprintf('Cannot lock the id sequence file: %s', $sequencePath));
            }

            $next = max($this->readLastId($handle), $this->maxExistingId()) + 1;

            if (!ftruncate($handle, 0) || !rewind($handle) || fwrite($handle, (string) $next) === false) {
                throw new RuntimeException(sprintf('Cannot write the id sequence file: %s', $sequencePath));
            }

            fflush($handle);

            return new TaskId($next);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     */
    private function readLastId($handle): int
    {
        rewind($handle);
        $content = stream_get_contents($handle);

        if (!is_string($content) || trim($content) === '') {
            return 0;
        }

        return max(0, (int) trim($content));
    }

    private function maxExistingId(): int
    {
        $max = 0;

        foreach ($this->repository->findAll() as $task) {
            $max = max($max, $task->id()->value());
        }

        return $max;
    }
}
