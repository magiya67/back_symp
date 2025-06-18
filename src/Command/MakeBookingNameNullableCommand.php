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
    name: 'app:booking:make-name-nullable',
    description: 'Делает колонку name в таблице booking nullable.'
)]
final class MakeBookingNameNullableCommand extends Command
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
        $this->connection->executeStatement('ALTER TABLE booking ALTER COLUMN name DROP NOT NULL');
        $output->writeln('<info>Колонка name сделана nullable в таблице booking.</info>');

        return Command::SUCCESS;
    }
}
