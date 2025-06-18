<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function sprintf;

/** @psalm-suppress UnusedClass */
#[AsCommand(
    name: 'app:booking:check-table',
    description: 'Проверяет структуру таблицы booking через Doctrine.'
)]
final class CheckBookingTableCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns('booking');
        $output->writeln('<info>Структура таблицы booking:</info>');
        foreach ($columns as $column) {
            $output->writeln(sprintf(
                '<comment>%s</comment> (тип: %s, nullable: %s)',
                $column->getName(),
                $column->getType()->getSQLDeclaration([], $this->connection->getDatabasePlatform()),
                $column->getNotnull() ? 'false' : 'true'
            ));
        }

        return Command::SUCCESS;
    }
}
