<?php

declare(strict_types=1);

namespace App\Task\Interface\Console;

use App\Task\Application\Command\AddTaskCommand;
use App\Task\Application\Handler\AddTaskHandler;
use DomainException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'task:add',
    description: 'Create a new task',
)]
final class AddTaskConsoleCommand extends Command
{
    public function __construct(
        private readonly AddTaskHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'description',
            InputArgument::REQUIRED,
            'Task description',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $description = (string) $input->getArgument('description');
            $taskId = ($this->handler)(new AddTaskCommand($description));
        } catch (DomainException|RuntimeException $e) {
            $io->error(sprintf('Failed to create task: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(sprintf('Task created. ID: %s', $taskId->value()));

        return Command::SUCCESS;
    }
}
