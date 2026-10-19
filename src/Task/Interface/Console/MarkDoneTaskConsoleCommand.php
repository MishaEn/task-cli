<?php

declare(strict_types = 1);

namespace App\Task\Interface\Console;

use App\Task\Application\Command\AddTaskCommand;
use App\Task\Application\Command\MarkDoneTaskCommand;
use App\Task\Application\Handler\MarkDoneTaskHandler;
use App\Task\Domain\ValueObject\TaskId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'task:mark-done',
    description: 'Mark task as done',
)]
class MarkDoneTaskConsoleCommand extends Command
{
    public function __construct(
        private readonly MarkDoneTaskHandler $handler,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'id',
            InputArgument::REQUIRED,
            'Task ID'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $id = TaskId::fromString($input->getArgument('id'));

        try {
            ($this->handler)(new MarkDoneTaskCommand($id));
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to mark task done: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $io->success(sprintf('Task marked done: %s', $id->value()));

        return Command::SUCCESS;
    }
}
