<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\House;
use App\Repository\HouseRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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
final class HouseControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $em;

    private HouseRepository $houseRepository;

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
        $this->em->beginTransaction();
        $this->createTestHouse();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->em->rollback();
        parent::tearDown();
    }

    public function testGetHouses(): void
    {
        $this->client->request('GET', '/api/houses');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        self::assertIsArray($response);
        self::assertGreaterThan(0, count($response));
        self::assertArrayHasKey(0, $response);
        /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
        self::assertIsArray($response[0]);
        self::assertArrayHasKey('name', $response[0]);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('Test House', $response[0]['name']);
    }

    public function testGetHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        $houseId = $house->getId();
        self::assertNotNull($houseId);
        $this->client->request('GET', '/api/houses/' . $houseId);

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('Test House', $response['name']);
    }

    public function testGetHouseNotFound(): void
    {
        $this->client->request('GET', '/api/houses/999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateHouse(): void
    {
        $this->client->request('POST', '/api/houses', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'name' => 'New House',
            'price' => 200,
            'location' => 'New Location',
            'description' => 'New Description',
            'image' => 'new.jpg',
        ]));

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        self::assertIsArray($response);
        self::assertArrayHasKey('name', $response);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('New House', $response['name']);
        self::assertArrayHasKey('price', $response);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame(200, $response['price']);
        self::assertArrayHasKey('location', $response);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('New Location', $response['location']);
        self::assertArrayHasKey('description', $response);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('New Description', $response['description']);
        self::assertArrayHasKey('image', $response);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('new.jpg', $response['image']);
    }

    public function testCreateHouseWithInvalidData(): void
    {
        $this->client->request('POST', '/api/houses', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'name' => '',
            'price' => -100,
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        $houseId = $house->getId();
        self::assertNotNull($houseId);
        $this->client->request('PUT', '/api/houses/' . $houseId, [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'name' => 'Updated House',
            'price' => 150,
        ]));

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame('Updated House', $response['name']);
        /** @psalm-suppress PossiblyUndefinedStringArrayOffset */
        self::assertSame(150, $response['price']);
    }

    public function testDeleteHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);

        $houseId = $house->getId();
        self::assertNotNull($houseId);
        $this->client->request('DELETE', '/api/houses/' . $houseId);

        $this->assertResponseIsSuccessful();
        self::assertNull($this->houseRepository->find($houseId));
    }

    public function testDeleteHouseNotFound(): void
    {
        $this->client->request('DELETE', '/api/houses/999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteBookedHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        self::assertNotNull($house);
        // Создаем бронирование для дома
        $booking = new \App\Entity\Booking();
        $booking->setHouse($house)
            ->setPhone('1234567890')
            ->setMessage('Test comment')
            ->setCreatedAt(new DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $houseId = $house->getId();
        self::assertNotNull($houseId);
        $this->client->request('DELETE', '/api/houses/' . $houseId);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $response = json_decode($content, true);
        self::assertSame(['error' => 'Cannot delete a booked house'], $response);
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
