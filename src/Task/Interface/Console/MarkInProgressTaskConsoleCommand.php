<?php

namespace App\Task\Interface\Console;

use App\Task\Application\Command\MarkDoneTaskCommand;
use App\Task\Application\Command\MarkInProgressTaskCommand;
use App\Task\Application\Handler\MarkInProgressTaskHandler;
use App\Task\Domain\ValueObject\TaskId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'task:mark-is-progress',
    description: 'Mark task as progress',
)]
class MarkInProgressTaskConsoleCommand extends Command
{
    public function __construct(
        private readonly MarkInProgressTaskHandler $handler,
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
            ($this->handler)(new MarkInProgressTaskCommand($id));
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to start task: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        $io->success(sprintf('Task start: %s', $id->value()));

        return Command::SUCCESS;
    }
}
