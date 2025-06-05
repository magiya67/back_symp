<?php

namespace App\Tests\TestCase;

use Symfony\Component\Filesystem\Filesystem;

trait TestDataTrait
{
    protected string $testHousesFile;
    protected string $testBookingsFile;
    protected Filesystem $filesystem;

    protected function setupTestData(): void
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
    }

    protected function cleanupTestData(): void
    {
        $this->filesystem->remove([$this->testHousesFile, $this->testBookingsFile]);
    }
} 