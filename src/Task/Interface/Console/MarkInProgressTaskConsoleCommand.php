<?php

declare(strict_types=1);

namespace App\Task\Interface\Console;

use App\Task\Application\Command\MarkInProgressTaskCommand;
use App\Task\Application\Handler\MarkInProgressTaskHandler;
use App\Task\Domain\ValueObject\TaskId;
use DomainException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'task:mark-in-progress',
    description: 'Mark task as in progress',
)]
final class MarkInProgressTaskConsoleCommand extends Command
{
    public function __construct(
        private readonly MarkInProgressTaskHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'Task ID',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $id = TaskId::fromString((string) $input->getArgument('id'));
            ($this->handler)(new MarkInProgressTaskCommand($id));
        } catch (DomainException|RuntimeException $e) {
            $io->error(sprintf('Failed to move task to in-progress: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(sprintf('Task moved to in-progress. ID: %s', $id->value()));

        return Command::SUCCESS;
    }
}
