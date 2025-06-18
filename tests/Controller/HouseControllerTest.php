<?php

namespace App\Tests\Controller;

use App\Entity\House;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class HouseControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;
    private HouseRepository $houseRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get(EntityManagerInterface::class);
        $this->houseRepository = $this->em->getRepository(House::class);
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

    public function testGetHouses(): void
    {
        $this->client->request('GET', '/api/houses');
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(1, $response);
        $this->assertEquals('Test House', $response[0]['name']);
    }

    public function testGetHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $this->client->request('GET', '/api/houses/' . $house->getId());
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test House', $response['name']);
    }

    public function testGetHouseNotFound(): void
    {
        $this->client->request('GET', '/api/houses/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateHouse(): void
    {
        $this->client->request(
            'POST',
            '/api/houses',
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
    }

    public function testCreateHouseInvalidData(): void
    {
        $this->client->request(
            'POST',
            '/api/houses',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New House',
                'price' => 200
            ])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $this->client->request(
            'PUT',
            '/api/houses/' . $house->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated House',
                'price' => 300,
                'location' => 'Updated Location',
                'description' => 'Updated Description',
                'image' => 'updated.jpg'
            ])
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated House', $response['name']);
        $this->assertEquals(300, $response['price']);
        $this->assertEquals('Updated Location', $response['location']);
        $this->assertEquals('Updated Description', $response['description']);
        $this->assertEquals('updated.jpg', $response['image']);
    }

    public function testUpdateHouseNotFound(): void
    {
        $this->client->request(
            'PUT',
            '/api/houses/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated House',
                'price' => 300,
                'location' => 'Updated Location',
                'description' => 'Updated Description',
                'image' => 'updated.jpg'
            ])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        $this->client->request('DELETE', '/api/houses/' . $house->getId());
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['message' => 'House deleted successfully'], $response);
    }

    public function testDeleteHouseNotFound(): void
    {
        $this->client->request('DELETE', '/api/houses/999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteBookedHouse(): void
    {
        $house = $this->houseRepository->findOneBy(['name' => 'Test House']);
        // Создаем бронирование для дома
        $booking = new \App\Entity\Booking();
        $booking->setHouse($house)
            ->setPhone('1234567890')
            ->setMessage('Test comment')
            ->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($booking);
        $this->em->flush();

        $this->client->request('DELETE', '/api/houses/' . $house->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(['error' => 'Cannot delete a booked house'], $response);
    }
} 