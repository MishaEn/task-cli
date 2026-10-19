<?php

declare(strict_types=1);

namespace App\Tests\Interface\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class TaskCommandsTest extends KernelTestCase
{
    private Application $application;
    private string $tasksFile;
    private string $sequenceFile;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $kernel = self::bootKernel(['environment' => 'test']);

        $this->application = new Application($kernel);
        $this->application->setAutoExit(false);

        $this->tasksFile = $kernel->getProjectDir() . '/var/tasks.test.json';
        $this->sequenceFile = $this->tasksFile . '.seq';

        $this->removeStorageFiles();
    }

    protected function tearDown(): void
    {
        $this->removeStorageFiles();
        parent::tearDown();
    }

    public function testAddAndListTasks(): void
    {
        $add = $this->execute('task:add', ['description' => 'Buy milk']);
        self::assertSame(0, $add->getStatusCode());
        self::assertStringContainsString('ID: 1', $add->getDisplay());

        $list = $this->execute('task:list');
        self::assertSame(0, $list->getStatusCode());
        self::assertStringContainsString('Buy milk', $list->getDisplay());
        self::assertStringContainsString('Total: 1 task(s).', $list->getDisplay());
    }

    public function testListWithoutStatusReturnsAllTasks(): void
    {
        $this->execute('task:add', ['description' => 'First']);
        $this->execute('task:add', ['description' => 'Second']);

        $list = $this->execute('task:list');

        self::assertSame(0, $list->getStatusCode());
        self::assertStringContainsString('Total: 2 task(s).', $list->getDisplay());
    }

    public function testListFiltersByStatus(): void
    {
        $this->execute('task:add', ['description' => 'First']);
        $this->execute('task:add', ['description' => 'Second']);
        $this->execute('task:mark-in-progress', ['id' => '1']);

        $list = $this->execute('task:list', ['status' => 'in-progress']);

        self::assertSame(0, $list->getStatusCode());
        self::assertStringContainsString('First', $list->getDisplay());
        self::assertStringNotContainsString('Second', $list->getDisplay());
        self::assertStringContainsString('Total: 1 task(s).', $list->getDisplay());
    }

    public function testUnknownStatusIsRejected(): void
    {
        $list = $this->execute('task:list', ['status' => 'nonsense']);

        self::assertSame(1, $list->getStatusCode());
        self::assertStringContainsString('Unknown status "nonsense"', $list->getDisplay());
    }

    public function testUpdateDescription(): void
    {
        $this->execute('task:add', ['description' => 'Old description']);

        $update = $this->execute('task:update', ['id' => '1', 'description' => 'New description']);

        self::assertSame(0, $update->getStatusCode());
        self::assertStringContainsString('New description', $this->execute('task:list')->getDisplay());
    }

    public function testMarkInProgressAndDone(): void
    {
        $this->execute('task:add', ['description' => 'Task']);

        $inProgress = $this->execute('task:mark-in-progress', ['id' => '1']);
        self::assertSame(0, $inProgress->getStatusCode());
        self::assertStringContainsString('in-progress', $this->execute('task:list')->getDisplay());

        $done = $this->execute('task:mark-done', ['id' => '1']);
        self::assertSame(0, $done->getStatusCode());
        self::assertStringContainsString('done', $this->execute('task:list')->getDisplay());
    }

    public function testMarkInProgressFromDoneIsRejected(): void
    {
        $this->execute('task:add', ['description' => 'Task']);
        $this->execute('task:mark-done', ['id' => '1']);

        $result = $this->execute('task:mark-in-progress', ['id' => '1']);

        self::assertSame(1, $result->getStatusCode());
        self::assertStringContainsString('Cannot start task in status "done"', $result->getDisplay());
    }

    public function testDeleteExistingAndMissingTask(): void
    {
        $this->execute('task:add', ['description' => 'Task']);

        $deleted = $this->execute('task:delete', ['id' => '1']);
        self::assertSame(0, $deleted->getStatusCode());
        self::assertStringContainsString('No tasks found', $this->execute('task:list')->getDisplay());

        $missing = $this->execute('task:delete', ['id' => '999']);
        self::assertSame(1, $missing->getStatusCode());
        self::assertStringContainsString('Task with id 999 not found', $missing->getDisplay());
    }

    public function testInvalidIdIsReportedWithoutCrash(): void
    {
        $update = $this->execute('task:update', ['id' => 'abc', 'description' => 'Task']);
        self::assertSame(1, $update->getStatusCode());
        self::assertStringContainsString('positive integer', $update->getDisplay());

        $delete = $this->execute('task:delete', ['id' => 'abc']);
        self::assertSame(1, $delete->getStatusCode());
        self::assertStringContainsString('positive integer', $delete->getDisplay());
    }

    public function testBlankAndTooLongDescriptionsAreRejected(): void
    {
        $blank = $this->execute('task:add', ['description' => '   ']);
        self::assertSame(1, $blank->getStatusCode());
        self::assertStringContainsString('cannot be empty', $blank->getDisplay());

        $tooLong = $this->execute('task:add', ['description' => str_repeat('a', 256)]);
        self::assertSame(1, $tooLong->getStatusCode());
        self::assertStringContainsString('cannot exceed 255 characters', $tooLong->getDisplay());
    }

    public function testEmptyListShowsWarning(): void
    {
        $list = $this->execute('task:list');

        self::assertSame(0, $list->getStatusCode());
        self::assertStringContainsString('No tasks found with status "any"', $list->getDisplay());
    }

    /**
     * @param array<string, string> $input
     */
    private function execute(string $name, array $input = []): CommandTester
    {
        $tester = new CommandTester($this->application->find($name));
        $tester->execute($input);

        return $tester;
    }

    private function removeStorageFiles(): void
    {
        @unlink($this->tasksFile);
        @unlink($this->tasksFile . '.tmp');
        @unlink($this->sequenceFile);
    }
}
