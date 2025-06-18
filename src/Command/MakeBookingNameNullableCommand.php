<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:booking:make-name-nullable',
    description: 'Делает поле name в таблице booking nullable.'
)]
class MakeBookingNameNullableCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection->executeStatement('ALTER TABLE booking ALTER COLUMN name DROP NOT NULL');
        $output->writeln('<info>Поле name теперь nullable.</info>');
        return Command::SUCCESS;
    }
} 