<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:booking:drop-name-column',
    description: 'Удаляет столбец name из таблицы booking, если он существует.'
)]
class DropBookingNameColumnCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection->executeStatement('ALTER TABLE booking DROP COLUMN IF EXISTS name');
        $output->writeln('<info>Столбец name удалён (если он существовал).</info>');
        return Command::SUCCESS;
    }
} 