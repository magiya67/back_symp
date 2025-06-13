<?php

namespace App\Tests\Controller;

use App\Service\HouseDataService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HouseControllerTest extends WebTestCase
{
    private string $testDataDir;
    private string $housesFile;
    private string $bookingsFile;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testDataDir = sys_get_temp_dir() . '/test_data';
        $this->housesFile = $this->testDataDir . '/houses.csv';
        $this->bookingsFile = $this->testDataDir . '/bookings.csv';
        
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0777, true);
        }
        
        $housesData = [
            ['id', 'name', 'price', 'location', 'description', 'image'],
            ['1', 'Test House', '100', 'Test Location', 'Test Description', 'test.jpg']
        ];
        $fp = fopen($this->housesFile, 'w');
        foreach ($housesData as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);

        // Подмена BookingDataService в контейнере
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->getContainer()->set(
            \App\Service\BookingDataService::class,
            new \App\Service\BookingDataService($this->housesFile, $this->bookingsFile)
        );
        $this->client = $client;
    }

    protected function tearDown(): void
    {
        // Clean up test data files
        if (file_exists($this->housesFile)) {
            unlink($this->housesFile);
        }
        if (file_exists($this->bookingsFile)) {
            unlink($this->bookingsFile);
        }
        if (is_dir($this->testDataDir)) {
            rmdir($this->testDataDir);
        }
        
        parent::tearDown();
    }

    public function testGetHouses(): void
    {
        $this->client->request('GET', '/api/houses/list');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertEquals('Test House', $response[0]['name']);
    }

    public function testGetHouse(): void
    {
        $this->client->request('GET', '/api/houses/get/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test House', $response['name']);
    }

    public function testGetHouseNotFound(): void
    {
        $this->client->request('GET', '/api/houses/get/999');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateHouse(): void
    {
        $this->client->request(
            'POST',
            '/api/houses/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New House',
                'price' => 200,
                'location' => 'New Location',
                'description' => 'New Description',
                'image' => 'new.jpg'
            ])
        );
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('New House', $response['name']);
        $this->assertEquals(200, $response['price']);
        $this->assertEquals('New Location', $response['location']);
        $this->assertEquals('New Description', $response['description']);
        $this->assertEquals('new.jpg', $response['image']);
        
        // Verify house was saved
        $houses = $this->getHousesFromFile();
        $this->assertCount(2, $houses);
        $this->assertEquals('New House', $houses[1]['name']);
    }

    public function testCreateHouseInvalidData(): void
    {
        $this->client->request(
            'POST',
            '/api/houses/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => '',  // Invalid empty name
                'price' => -100,  // Invalid negative price
                'location' => 'New Location',
                'description' => 'New Description',
                'image' => 'new.jpg'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateHouse(): void
    {
        $this->client->request(
            'PUT',
            '/api/houses/update/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'price' => 150,
                'description' => 'Updated Description'
            ])
        );
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(150, $response['price']);
        $this->assertEquals('Updated Description', $response['description']);
        
        // Verify house was updated
        $houses = $this->getHousesFromFile();
        $this->assertCount(1, $houses);
        $this->assertEquals(150, $houses[0]['price']);
        $this->assertEquals('Updated Description', $houses[0]['description']);
    }

    public function testUpdateHouseNotFound(): void
    {
        $this->client->request(
            'PUT',
            '/api/houses/update/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'price' => 150,
                'description' => 'Updated Description'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteHouse(): void
    {
        $this->client->request('DELETE', '/api/houses/delete/1');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['message' => 'House deleted successfully'], $response);
        
        // Verify house was deleted
        $houses = $this->getHousesFromFile();
        $this->assertCount(0, $houses);
    }

    public function testDeleteHouseNotFound(): void
    {
        $this->client->request('DELETE', '/api/houses/delete/999');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'House not found'], $response);
    }

    public function testDeleteBookedHouse(): void
    {
        // Create a booking for house 1
        $bookingsData = [
            ['id', 'house_id', 'user_id', 'start_date', 'end_date'],
            ['1', '1', '1', '2024-01-01', '2024-01-10']
        ];
        
        $fp = fopen($this->bookingsFile, 'w');
        foreach ($bookingsData as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);

        $this->client->request('DELETE', '/api/houses/delete/1');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Cannot delete a booked house'], $response);
        
        // Verify house still exists
        $houses = $this->getHousesFromFile();
        $this->assertCount(1, $houses);
    }

    public function testDeleteHouseWithEmptyList(): void
    {
        // Create empty houses file with headers only
        $housesData = [
            ['id', 'name', 'price', 'location', 'description', 'image']
        ];
        
        $fp = fopen($this->housesFile, 'w');
        foreach ($housesData as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);

        $this->client->request('DELETE', '/api/houses/delete/1');
        
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'House not found'], $response);
    }

    public function testDeleteHouseWithFreeList(): void
    {
        $this->client->request('GET', '/api/houses/free');
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertEquals('Test House', $response[0]['name']);
    }

    private function getHousesFromFile(): array
    {
        $houses = [];
        if (($handle = fopen($this->housesFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                $houses[] = array_combine($headers, $data);
            }
            fclose($handle);
        }
        return $houses;
    }
} 