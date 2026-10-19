<?php

declare(strict_types=1);

namespace App\Task\Interface\Console;

use App\Task\Application\Command\ListTaskCommand;
use App\Task\Application\Handler\ListTaskHandler;
use App\Task\Domain\Enum\TaskStatus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'task:lists',
    description: 'Show list of tasks',
)]
class ListTaskConsoleCommand extends Command
{
    public function __construct(
        private readonly ListTaskHandler $handler,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'status',
            InputArgument::OPTIONAL,
            'Task status'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $statusInput = $input->getArgument('status');

        $status = null;

        if ($statusInput !== null) {
            $status = TaskStatus::tryFrom($statusInput);

            if ($status === null) {
                $io->error(sprintf(
                    'Unknown status "%s". Allowed: %s.',
                    $statusInput,
                    implode(', ', array_column(TaskStatus::cases(), 'value')),
                ));
                return Command::FAILURE;
            }
        }

        try {
            $tasks = ($this->handler)(new ListTaskCommand($status));
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to list tasks: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        if ($tasks === []) {
            $io->warning(sprintf('No tasks found with status "%s".', $status->value));
            return Command::SUCCESS;
        }

        $io->title(sprintf('Tasks with status "%s"', $status->value));

        $rows = array_map(
            static fn($task): array => [
                $task->id()->value(),
                $task->description(),
                $task->status()->value,
                $task->createdAt()->format('Y-m-d H:i'),
                $task->updatedAt()->format('Y-m-d H:i'),
            ],
            $tasks,
        );

        $io->table(
            ['ID', 'Description', 'Status', 'Created', 'Updated'],
            $rows,
        );

        $io->success(sprintf('Total: %d task(s).', count($tasks)));

        return Command::SUCCESS;
    }
}
