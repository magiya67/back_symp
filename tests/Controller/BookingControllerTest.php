<?php

namespace App\Tests\Controller;

use App\BookingDataService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    public function testCreateBooking()
    {
        $client = static::createClient();
        $testFile = __DIR__ . '/../resources/bookings_test.csv';
        $client->getContainer()->set(BookingDataService::class, new BookingDataService($testFile));

        $client->request('POST', '/api/booking', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'phone' => '+7 (999) 111-2233',
            'house_id' => 1,
            'comment' => 'Тестовая заявка'
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('+7 (999) 111-2233', $response['booking']['phone']);
    }

    public function testUpdateBooking()
    {
        $client = static::createClient();
        $testFile = __DIR__ . '/../resources/bookings_test.csv';
        $client->getContainer()->set(BookingDataService::class, new BookingDataService($testFile));

        $client->request('PUT', '/api/booking/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'comment' => 'Обновленный комментарий'
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals('Обновленный комментарий', $response['booking']['comment']);
    }

    public function testDeleteBooking()
    {
        $client = static::createClient();
        $testFile = __DIR__ . '/../resources/bookings_test.csv';
        $client->getContainer()->set(BookingDataService::class, new BookingDataService($testFile));

        $client->request('PUT', '/api/booking/1/delete');

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
        $this->assertEquals(1, $response['deleted_id']);
    }
} 