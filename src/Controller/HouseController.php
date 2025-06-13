<?php

namespace App\Controller;

use App\Entity\House;
use App\Repository\HouseRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/houses')]
final class HouseController extends AbstractController
{
    public function __construct(
        private readonly HouseRepository $houseRepository,
        private readonly BookingRepository $bookingRepository,
        private readonly EntityManagerInterface $em
    ) {}

    #[Route('/free', name: 'api_houses_free', methods: ['GET'])]
    public function freeHouses(): JsonResponse
    {
        $houses = $this->houseRepository->findAll();
        $freeHouses = array_filter($houses, function(House $house) {
            return $house->getBookings()->isEmpty();
        });
        return $this->json(array_values(array_map(fn($h) => $this->serializeHouse($h), $freeHouses)));
    }

    #[Route('/{id}', name: 'api_houses_delete', methods: ['DELETE'])]
    public function deleteHouse(int $id): JsonResponse
    {
        $house = $this->houseRepository->find($id);
        if (!$house) {
            return $this->json(['error' => 'House not found'], 404);
        }
        $this->em->refresh($house);
        if (!$house->getBookings()->isEmpty()) {
            return $this->json(['error' => 'Cannot delete a booked house'], 400);
        }
        $this->houseRepository->remove($house, true);
        return $this->json(['message' => 'House deleted successfully'], 200);
    }

    #[Route('/{id}', name: 'api_houses_update', methods: ['PUT'])]
    public function updateHouse(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid input'], 400);
        }
        $house = $this->houseRepository->find($id);
        if (!$house) {
            return $this->json(['error' => 'House not found'], 404);
        }
        foreach (['name', 'price', 'location', 'description', 'image'] as $field) {
            if (isset($data[$field])) {
                $setter = 'set' . ucfirst($field);
                $house->$setter($data[$field]);
            }
        }
        $this->em->flush();
        return $this->json($this->serializeHouse($house));
    }

    #[Route('', name: 'api_houses_create', methods: ['POST'])]
    public function createHouse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data['name']) || !isset($data['price'], $data['location'], $data['description'], $data['image'])) {
            return $this->json(['error' => 'Invalid input'], 400);
        }
        $house = new House();
        $house->setName($data['name'])
            ->setPrice($data['price'])
            ->setLocation($data['location'])
            ->setDescription($data['description'])
            ->setImage($data['image']);
        $this->houseRepository->save($house, true);
        return $this->json($this->serializeHouse($house));
    }

    #[Route('/{id}', name: 'api_houses_get', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        $house = $this->houseRepository->find($id);
        if (!$house) {
            return $this->json(['error' => 'House not found'], 404);
        }
        return $this->json($this->serializeHouse($house));
    }

    #[Route('', name: 'api_houses_get_all', methods: ['GET'])]
    public function getHouses(): JsonResponse
    {
        $houses = $this->houseRepository->findAll();
        return $this->json(array_map(fn($h) => $this->serializeHouse($h), $houses));
    }

    private function serializeHouse(House $house): array
    {
        return [
            'id' => $house->getId(),
            'name' => $house->getName(),
            'price' => $house->getPrice(),
            'location' => $house->getLocation(),
            'description' => $house->getDescription(),
            'image' => $house->getImage(),
        ];
    }
} 