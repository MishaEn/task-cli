<?php

declare(strict_types=1);

namespace App\Task\Application\Lock;

interface TaskStorageLockInterface
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function synchronized(callable $operation): mixed;
}
