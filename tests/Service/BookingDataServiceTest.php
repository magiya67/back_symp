<?php

namespace App\Tests\Service;

use App\BookingDataService;
use PHPUnit\Framework\TestCase;

class BookingDataServiceTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = __DIR__ . '/../resources/bookings_test.csv';
        copy(__DIR__ . '/../resources/bookings_fixture.csv', $this->testFile);
    }

    public function testGetAll()
    {
        $service = new BookingDataService($this->testFile);
        $all = $service->getAll();
        $this->assertCount(2, $all);
        $this->assertEquals('+7 (111) 111-1111', $all[0]['phone']);
    }

    public function testAdd()
    {
        $service = new BookingDataService($this->testFile);
        $service->add(['phone' => '+7 (333) 333-3333', 'house_id' => 3, 'comment' => 'Новая заявка', 'created_at' => '2024-01-03T12:00:00+00:00']);
        $all = $service->getAll();
        $this->assertCount(3, $all);
        $this->assertEquals('+7 (333) 333-3333', $all[2]['phone']);
    }

    public function testUpdate()
    {
        $service = new BookingDataService($this->testFile);
        $service->update(1, ['comment' => 'Обновленный комментарий']);
        $all = $service->getAll();
        $this->assertEquals('Обновленный комментарий', $all[0]['comment']);
    }

    public function testDelete()
    {
        $service = new BookingDataService($this->testFile);
        $service->delete(1);
        $all = $service->getAll();
        $this->assertCount(1, $all);
        $this->assertEquals('2', $all[0]['id']);
    }
} 