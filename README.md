# Task CLI

A console task tracker built on Symfony 8.1 with Domain / Application / Infrastructure / Interface layers.
Tasks are stored in a JSON file, so no database is required.

Original assignment: https://roadmap.sh/projects/task-tracker

## Requirements

- PHP >= 8.4
- Extensions: `ctype`, `iconv`, `mbstring`
- Composer

## Installation

```bash
composer install
```

Verify that the application works:

```bash
php bin/console about
php bin/console list task
```

If the Symfony CLI is installed, you can use `symfony console` instead of `php bin/console`.

## Commands

| Command | Purpose |
|---|---|
| `task:add <description>` | Create a new task |
| `task:list [status]` | Show all tasks or tasks with the given status |
| `task:update <id> <description>` | Update a task description |
| `task:delete <id>` | Delete a task |
| `task:mark-in-progress <id>` | Move a task from `todo` to `in-progress` |
| `task:mark-done <id>` | Mark a task as done |

Available statuses: `todo`, `in-progress`, `done`.

### Examples

```bash
# Create a task
php bin/console task:add "Buy milk"

# Show all tasks
php bin/console task:list

# Show only tasks that are in progress
php bin/console task:list in-progress

# Update the description of task 1
php bin/console task:update 1 "Buy oat milk"

# Move a task to in-progress
php bin/console task:mark-in-progress 1

# Mark a task as done
php bin/console task:mark-done 1

# Delete a task
php bin/console task:delete 1
```

In PowerShell, quote descriptions that contain spaces in the same way:
`php bin/console task:add "Buy milk"`.

On error (unknown id, invalid status, empty description, or a description longer than 255 characters),
the command prints an error message and exits with code `1`.

## Data storage

By default, tasks are stored in `var/tasks.json`. The path is configured through the `TASKS_FILE`
environment variable in `.env`:

```dotenv
TASKS_FILE=var/tasks.json
```

The file is written atomically, and all modifying operations are protected by a lock
(`symfony/lock`), so concurrent command runs do not lose tasks.

A `var/tasks.json.seq` file is created next to it. It stores the last issued id and prevents
id reuse after a task is deleted. You do not have to remove it: when missing, the counter
is rebuilt from the highest id in the task file.

The `var/` directory is not committed to git.

## Tests and static analysis

Tests use a separate file, `var/tasks.test.json` (configured in `.env.test`):

```bash
php vendor/bin/phpunit
```

Static analysis (PHPStan, level 6):

```bash
vendor/bin/phpstan analyse
```

Container and YAML checks:

```bash
php bin/console lint:container
php bin/console lint:yaml config/
```

## Project structure

```
src/Task/
├── Domain/          # Task entity, TaskId/TaskDescription value objects, enum, ports
├── Application/     # commands, handlers, TaskFinder, locking and id generation ports
├── Infrastructure/  # file repository, id generator, symfony/lock adapter
└── Interface/       # Symfony console commands
```

The domain layer has no Symfony dependencies. Tasks are written through the
`TaskRepositoryInterface` port, implemented by `FileTaskRepository`.
