<?php

namespace App\Tests\TestCase;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use \App\Tests\TestCase\TestDataTrait;

abstract class BaseTestCase extends TestCase
{
    use TestDataTrait;

    public string $testHousesFile;
    public string $testBookingsFile;
    public Filesystem $filesystem;

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

    protected function tearDown(): void
    {
        // Clean up temporary files
        $this->filesystem->remove([$this->testHousesFile, $this->testBookingsFile]);

        $this->cleanupTestData();
    }
} 