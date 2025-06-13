<?php

namespace App\Tests\Controller;

use App\Service\BookingDataService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookingControllerTest extends WebTestCase
{
    private string $testDataDir;
    private string $housesFile;
    private string $bookingsFile;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDataDir = sys_get_temp_dir() . '/test_data_booking';
        $this->housesFile = $this->testDataDir . '/houses.csv';
        $this->bookingsFile = $this->testDataDir . '/bookings.csv';
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0777, true);
        }
        // Houses test data
        $housesData = [
            ['id', 'name', 'price', 'location', 'description', 'image'],
            ['1', 'Test House', '100', 'Test Location', 'Test Description', 'test.jpg']
        ];
        $fp = fopen($this->housesFile, 'w');
        foreach ($housesData as $row) {
            fputcsv($fp, $row, ',', '"', '\\');
        }
        fclose($fp);
        $fp = fopen($this->bookingsFile, 'w');
        fputcsv($fp, ['id', 'phone', 'house_id', 'comment', 'created_at'], ',', '"', '\\');
        fclose($fp);
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

    public function testCreateBooking(): void
    {
        $data = [
            'phone' => '1234567890',
            'house_id' => 1,
            'comment' => 'Test comment'
        ];
        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('booking', $response);
        $this->assertEquals('1234567890', $response['booking']['phone']);
        $this->assertEquals(1, (int)$response['booking']['house_id']);
        $this->assertEquals('Test comment', $response['booking']['comment']);
    }

    public function testCreateBookingInvalidData(): void
    {
        $data = [
            'house_id' => 1
        ];
        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'phone and house_id are required'], $response);
    }

    public function testCreateBookingHouseNotFound(): void
    {
        $data = [
            'phone' => '1234567890',
            'house_id' => 999
        ];
        $this->client->request(
            'POST',
            '/api/bookings/create',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'House not found'], $response);
    }

    public function testGetBookings(): void
    {
        // Добавляем бронирование вручную в CSV
        $fp = fopen($this->bookingsFile, 'a');
        fputcsv($fp, ['1', '1234567890', '1', 'Test comment', date('c')], ',', '"', '\\');
        fclose($fp);
        $this->client->request('GET', '/api/bookings/booking_list');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertEquals('1', $response[0]['id']);
        $this->assertEquals('1234567890', $response[0]['phone']);
    }

    public function testGetBooking(): void
    {
        $fp = fopen($this->bookingsFile, 'a');
        fputcsv($fp, ['1', '1234567890', '1', 'Test comment', date('c')], ',', '"', '\\');
        fclose($fp);
        $this->client->request('GET', '/api/bookings/booking_get/1');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('1', $response['id']);
        $this->assertEquals('1234567890', $response['phone']);
    }

    public function testGetBookingNotFound(): void
    {
        $this->client->request('GET', '/api/bookings/booking_get/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateBooking(): void
    {
        $fp = fopen($this->bookingsFile, 'a');
        fputcsv($fp, ['1', '1234567890', '1', 'Test comment', date('c')], ',', '"', '\\');
        fclose($fp);
        $data = ['comment' => 'Updated comment'];
        $this->client->request(
            'PUT',
            '/api/bookings/booking_update/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['success' => true], $response);
    }

    public function testUpdateBookingNotFound(): void
    {
        $data = ['comment' => 'Updated comment'];
        $this->client->request(
            'PUT',
            '/api/bookings/booking_update/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Booking not found'], $response);
    }

    public function testDeleteBooking(): void
    {
        $fp = fopen($this->bookingsFile, 'a');
        fputcsv($fp, ['1', '1234567890', '1', 'Test comment', date('c')], ',', '"', '\\');
        fclose($fp);
        $this->client->request('DELETE', '/api/bookings/booking_delete/1');
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['message' => 'Booking deleted successfully'], $response);
        // Проверяем, что файл пустой (только заголовок)
        $rows = [];
        if (($handle = fopen($this->bookingsFile, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }
        $this->assertCount(1, $rows); // только заголовок
    }

    public function testDeleteBookingNotFound(): void
    {
        $this->client->request('DELETE', '/api/bookings/booking_delete/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Booking not found'], $response);
    }

    public function testListBookings(): void
    {
        // Добавляем бронирование вручную в CSV
        $fp = fopen($this->bookingsFile, 'a');
        fputcsv($fp, ['1', '1234567890', '1', 'Test comment', date('c')], ',', '"', '\\');
        fclose($fp);
        $this->client->request('GET', '/api/bookings/booking_list');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertEquals('1', $response[0]['id']);
        $this->assertEquals('1234567890', $response[0]['phone']);
    }
} 