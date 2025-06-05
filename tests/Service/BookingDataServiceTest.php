<?php

namespace App\Tests\Service;

use App\Service\BookingDataService;
use PHPUnit\Framework\TestCase;

class BookingDataServiceTest extends TestCase
{
    private string $housesFile;
    private string $bookingsFile;

    protected function setUp(): void
    {
        $this->housesFile = sys_get_temp_dir() . '/test_houses.csv';
        $this->bookingsFile = sys_get_temp_dir() . '/test_bookings.csv';
        // Создаем тестовые файлы
        file_put_contents($this->housesFile, "id,name,price,location,description,image\n1,Test House,100,Test Location,Test Description,test.jpg\n");
        file_put_contents($this->bookingsFile, "id,house_id,user_id,start_date,end_date\n");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->housesFile)) unlink($this->housesFile);
        if (file_exists($this->bookingsFile)) unlink($this->bookingsFile);
    }

    public function testGetHouses(): void
    {
        $service = new BookingDataService($this->housesFile, $this->bookingsFile);
        $houses = $service->getHouses();
        $this->assertCount(1, $houses);
        $this->assertEquals('Test House', $houses[0]['name']);
    }

    public function testSaveHouses(): void
    {
        $service = new BookingDataService($this->housesFile, $this->bookingsFile);
        $houses = [
            [
                'id' => 2,
                'name' => 'New House',
                'price' => 200,
                'location' => 'Loc',
                'description' => 'Desc',
                'image' => 'img.jpg'
            ]
        ];
        $result = $service->saveHouses($houses);
        $this->assertTrue($result);
        $loaded = $service->getHouses();
        $this->assertCount(1, $loaded);
        $this->assertEquals('New House', $loaded[0]['name']);
    }

    public function testGetBookings(): void
    {
        file_put_contents($this->bookingsFile, "id,house_id,user_id,start_date,end_date\n1,1,1,2024-01-01,2024-01-10\n");
        $service = new BookingDataService($this->housesFile, $this->bookingsFile);
        $bookings = $service->getBookings();
        $this->assertCount(1, $bookings);
        $this->assertEquals('1', $bookings[0]['house_id']);
    }

    public function testIsHouseBooked(): void
    {
        file_put_contents($this->bookingsFile, "id,house_id,user_id,start_date,end_date\n1,1,1,2024-01-01,2024-01-10\n");
        $service = new BookingDataService($this->housesFile, $this->bookingsFile);
        $this->assertTrue($service->isHouseBooked(1));
        $this->assertFalse($service->isHouseBooked(2));
    }
} 