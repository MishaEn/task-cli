<?php

declare(strict_types=1);

namespace App\Task\Infrastructure\Lock;

use App\Task\Application\Lock\TaskStorageLockInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Lock\LockFactory;

#[AsAlias(TaskStorageLockInterface::class)]
final readonly class SymfonyTaskStorageLock implements TaskStorageLockInterface
{
    public function __construct(
        private LockFactory $lockFactory,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function synchronized(callable $operation): mixed
    {
        $lock = $this->lockFactory->createLock('task-storage', 30.0);
        $lock->acquire(true);

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }
}
