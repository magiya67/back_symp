<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:booking:check-table',
    description: 'Проверяет структуру таблицы booking через Doctrine.'
)]
class CheckBookingTableCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns('booking');
        $output->writeln('<info>Структура таблицы booking:</info>');
        foreach ($columns as $column) {
            $output->writeln(sprintf(
                '<comment>%s</comment> (тип: %s, nullable: %s)',
                $column->getName(),
                $column->getType()->getName(),
                $column->getNotnull() ? 'false' : 'true'
            ));
        }
        return Command::SUCCESS;
    }
} 