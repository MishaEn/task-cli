<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Task\Domain\ValueObject\TaskDescription;
use PHPUnit\Framework\TestCase;

final class TaskDescriptionTest extends TestCase
{
    public function testItTrimsTheValue(): void
    {
        $description = new TaskDescription('  Buy milk  ');

        self::assertSame('Buy milk', $description->value());
    }

    public function testItRejectsABlankValue(): void
    {
        $this->expectException(\DomainException::class);

        new TaskDescription('   ');
    }

    public function testItRejectsAValueLongerThan255Characters(): void
    {
        $this->expectException(\DomainException::class);

        new TaskDescription(str_repeat('a', 256));
    }

    public function testItAccepts255Characters(): void
    {
        $description = new TaskDescription(str_repeat('a', 255));

        self::assertSame(255, mb_strlen($description->value()));
    }

    public function testEqualsComparesNormalizedValues(): void
    {
        self::assertTrue((new TaskDescription(' value '))->equals(new TaskDescription('value')));
        self::assertFalse((new TaskDescription('value'))->equals(new TaskDescription('other')));
    }
}
