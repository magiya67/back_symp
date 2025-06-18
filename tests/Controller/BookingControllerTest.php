<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Entity\House;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use function count;

/**
 * @psalm-suppress UnusedClass
 *
 * @internal
 *
 * @small
 *
 * @coversNothing
 */
final class BookingControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;

    private HouseRepository $houseRepository;

    private BookingRepository $bookingRepository;

    private KernelBrowser $client;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        /** @var EntityManagerInterface $em */
        $em = $this->client->getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;
        /** @var HouseRepository $houseRepository */
        $houseRepository = $this->em->getRepository(House::class);
        $this->houseRepository = $houseRepository;
        /** @var BookingRepository $bookingRepository */
        $bookingRepository = $this->em->getRepository(Booking::class);
        $this->bookingRepository = $bookingRepository;
        $this->em->beginTransaction();
        $this->createTestHouse();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testCreateBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        $this->client->request('POST', '/api/bookings', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'phone' => '1234567890',
            'house_id' => $house->getId(),
        ]));

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        self::assertIsArray($response);
        self::assertArrayHasKey('success', $response);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertTrue($response['success']);
        self::assertArrayHasKey('booking', $response);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertIsArray($response['booking']);
        self::assertArrayHasKey('phone', $response['booking']);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('1234567890', $response['booking']['phone']);
    }

    public function testCreateBookingWithInvalidData(): void
    {
        $this->client->request('POST', '/api/bookings', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'phone' => '',
            'house_id' => 999,
        ]));

        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetBookings(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        // Create a booking first
        $booking = new Booking();
        $booking->setPhone('1234567890');
        $booking->setHouse($house);
        $booking->setCreatedAt(new DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $this->client->request('GET', '/api/bookings');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        self::assertIsArray($response);
        self::assertGreaterThan(0, count($response));
        self::assertArrayHasKey(0, $response);
        /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
        self::assertIsArray($response[0]);
        self::assertArrayHasKey('phone', $response[0]);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('1234567890', $response[0]['phone']);
    }

    public function testGetBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        $booking = new Booking();
        $booking->setPhone('1234567890');
        $booking->setHouse($house);
        $booking->setCreatedAt(new DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $bookingId = $booking->getId();
        self::assertNotNull($bookingId);
        $this->client->request('GET', '/api/bookings/' . $bookingId);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('1234567890', $response['phone']);
    }

    public function testGetBookingNotFound(): void
    {
        $this->client->request('GET', '/api/bookings/999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        $booking = new Booking();
        $booking->setPhone('1234567890');
        $booking->setHouse($house);
        $booking->setCreatedAt(new DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $bookingId = $booking->getId();
        self::assertNotNull($bookingId);
        $this->client->request('PUT', '/api/bookings/' . $bookingId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'phone' => '0987654321',
        ]));

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('0987654321', $response['phone']);
    }

    public function testDeleteBooking(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        $booking = new Booking();
        $booking->setPhone('1234567890');
        $booking->setHouse($house);
        $booking->setCreatedAt(new DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $bookingId = $booking->getId();
        self::assertNotNull($bookingId);
        $this->client->request('DELETE', '/api/bookings/' . $bookingId);

        $this->assertResponseIsSuccessful();
        self::assertNull($this->bookingRepository->find($bookingId));
    }

    private function createTestHouse(): void
    {
        $house = new House();
        $house->setName('Test House');
        $house->setPrice(100);
        $house->setLocation('Test Location');
        $house->setDescription('Test Description');
        $house->setImage('test.jpg');
        $this->em->persist($house);
        $this->em->flush();
    }
}
