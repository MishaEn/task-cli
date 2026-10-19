<?php

declare(strict_types=1);

namespace App\Task\Interface\Console;

use App\Task\Application\Command\ListTaskCommand;
use App\Task\Application\Handler\ListTaskHandler;
use App\Task\Domain\Entity\Task;
use App\Task\Domain\Enum\TaskStatus;
use DomainException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'task:list',
    description: 'Show list of tasks',
)]
final class ListTaskConsoleCommand extends Command
{
    public function __construct(
        private readonly ListTaskHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'status',
            InputArgument::OPTIONAL,
            'Task status (todo, in-progress, done)',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $statusInput = $input->getArgument('status');
        $status = null;

        if ($statusInput !== null) {
            $status = TaskStatus::tryFrom((string) $statusInput);

            if ($status === null) {
                $allowed = array_map(
                    static fn (TaskStatus $case): string => $case->value,
                    TaskStatus::cases(),
                );

                $io->error(sprintf(
                    'Unknown status "%s". Allowed: %s.',
                    $statusInput,
                    implode(', ', $allowed),
                ));

                return Command::FAILURE;
            }
        }

        try {
            $tasks = ($this->handler)(new ListTaskCommand($status));
        } catch (DomainException|RuntimeException $e) {
            $io->error(sprintf('Failed to list tasks: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $statusLabel = $status->value ?? 'any';

        if ($tasks === []) {
            $io->warning(sprintf('No tasks found with status "%s".', $statusLabel));

            return Command::SUCCESS;
        }

        $io->title(sprintf('Tasks with status "%s"', $statusLabel));

        $rows = array_map(
            static fn (Task $task): array => [
                $task->id()->value(),
                $task->description()->value(),
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
