<?php

declare(strict_types=1);

namespace App\Tests\TestCase;

use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function dirname;

/** @psalm-suppress UnusedClass */
abstract class BaseTestCase extends TestCase
{
    use TestDataTrait;

    public string $testHousesFile;

    public string $testBookingsFile;

    public Filesystem $filesystem;

    #[Override]
    protected function setUp(): void
    {
        $this->testHousesFile = sys_get_temp_dir() . '/test_houses.csv';
        $this->testBookingsFile = sys_get_temp_dir() . '/test_bookings.csv';
        $this->filesystem = new Filesystem();

        // Copy test data files to temp directory
        $this->filesystem->copy(
            dirname(__DIR__) . '/data/test_houses.csv',
            $this->testHousesFile
        );
        $this->filesystem->copy(
            dirname(__DIR__) . '/data/test_bookings.csv',
            $this->testBookingsFile
        );

        $this->setupTestData();
    }

    #[Override]
    protected function tearDown(): void
    {
        // Clean up temporary files
        $this->filesystem->remove([$this->testHousesFile, $this->testBookingsFile]);

        $this->cleanupTestData();
    }
}
