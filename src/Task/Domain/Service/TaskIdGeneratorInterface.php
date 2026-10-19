<?php

declare(strict_types=1);

namespace App\Task\Domain\Service;

use App\Task\Domain\ValueObject\TaskId;

interface TaskIdGeneratorInterface
{
    public function next(): TaskId;
}
