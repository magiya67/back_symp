<?php

namespace App\Tests\Controller;

use App\Entity\House;
use App\Entity\Booking;
use App\Repository\HouseRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BookingControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private HouseRepository $houseRepository;
    private BookingRepository $bookingRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get(EntityManagerInterface::class);
        $this->houseRepository = $this->em->getRepository(House::class);
        $this->bookingRepository = $this->em->getRepository(Booking::class);
        $this->em->beginTransaction();
        $this->createTestHouse();
    }

    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    private function createTestHouse(): void
    {
        $house = new House();
        $house->setName('Test House')
            ->setPrice(100)
            ->setLocation('Test Location')
            ->setDescription('Test Description')
            ->setImage('test.jpg');
        $this->em->persist($house);
        $this->em->flush();
    }

    public function testCreateBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $data = [
            'phone' => '1234567890',
            'house_id' => $house->getId(),
            'comment' => 'Test comment',
            'name' => 'Test User'
        ];
        $this->client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('booking', $response);
        $this->assertEquals('1234567890', $response['booking']['phone']);
        $this->assertEquals($house->getId(), $response['booking']['house_id']);
        $this->assertEquals('Test comment', $response['booking']['comment']);
    }

    public function testCreateBookingInvalidData(): void
    {
        $data = [
            'house_id' => 1
        ];
        $this->client->request(
            'POST',
            '/api/bookings',
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
            '/api/bookings',
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
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $booking = new Booking();
        $booking->setHouse($house)
            ->setPhone('1234567890')
            ->setMessage('Test comment')
            ->setName('Test User')
            ->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $this->client->request('GET', '/api/bookings');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertEquals('1234567890', $response[0]['phone']);
    }

    public function testGetBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $booking = new Booking();
        $booking->setHouse($house)
            ->setPhone('1234567890')
            ->setMessage('Test comment')
            ->setName('Test User')
            ->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $this->client->request('GET', '/api/bookings/' . $booking->getId());
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('1234567890', $response['phone']);
    }

    public function testGetBookingNotFound(): void
    {
        $this->client->request('GET', '/api/bookings/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $booking = new Booking();
        $booking->setHouse($house)
            ->setPhone('1234567890')
            ->setMessage('Test comment')
            ->setName('Test User')
            ->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $this->client->request(
            'PUT',
            '/api/bookings/' . $booking->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'phone' => '9876543210',
                'comment' => 'Updated comment'
            ])
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('9876543210', $response['phone']);
        $this->assertEquals('Updated comment', $response['comment']);
    }

    public function testUpdateBookingNotFound(): void
    {
        $this->client->request(
            'PUT',
            '/api/bookings/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'phone' => '9876543210',
                'comment' => 'Updated comment'
            ])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $booking = new Booking();
        $booking->setHouse($house)
            ->setPhone('1234567890')
            ->setMessage('Test comment')
            ->setName('Test User')
            ->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $this->client->request('DELETE', '/api/bookings/' . $booking->getId());
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['message' => 'Booking deleted successfully'], $response);
    }

    public function testDeleteBookingNotFound(): void
    {
        $this->client->request('DELETE', '/api/bookings/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Booking not found'], $response);
    }
} 