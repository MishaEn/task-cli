<?php

declare(strict_types=1);

namespace App\Task\Interface\Console;

use App\Task\Application\Command\UpdateTaskCommand;
use App\Task\Application\Handler\UpdateTaskHandler;
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
    name: 'task:update',
    description: 'Update task description',
)]
final class UpdateTaskConsoleCommand extends Command
{
    public function __construct(
        private readonly UpdateTaskHandler $handler,
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
            $id = TaskId::fromString((string) $input->getArgument('id'));
            $description = (string) $input->getArgument('description');
            ($this->handler)(new UpdateTaskCommand($id, $description));
        } catch (DomainException|RuntimeException $e) {
            $io->error(sprintf('Failed to update task: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        $io->success(sprintf('Task updated. ID: %s', $id->value()));

        return Command::SUCCESS;
    }
}
