<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/** @psalm-suppress UnusedClass */
#[AsCommand(
    name: 'app:booking:drop-name-column',
    description: 'Удаляет колонку name из таблицы booking.'
)]
final class DropBookingNameColumnCommand extends Command
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
        $this->connection->executeStatement('ALTER TABLE booking DROP COLUMN IF EXISTS name');
        $output->writeln('<info>Колонка name удалена из таблицы booking.</info>');

        return Command::SUCCESS;
    }
}
