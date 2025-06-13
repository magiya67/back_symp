<?php

namespace App\Controller;

use App\Service\BookingDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/api/houses')]
final class HouseController extends AbstractController
{
    private const UPDATEABLE_FIELDS = ['name', 'address', 'price', 'location', 'description', 'image'];

    public function __construct(
        private readonly BookingDataService $bookingDataService
    ) {
    }

    #[Route('/free', name: 'api_houses_free', methods: ['GET'])]
    public function freeHouses(): JsonResponse
    {
        $houses = $this->bookingDataService->getHouses();
        $freeHouses = array_filter($houses, function($house) {
            return !$this->bookingDataService->isHouseBooked((int)$house['id']);
        });
        
        return $this->json(array_values($freeHouses));
    }

    #[Route('/delete/{id}', name: 'api_houses_delete', methods: ['DELETE'])]
    public function deleteHouse(int $id): JsonResponse
    {
        try {
            $houses = $this->bookingDataService->getHouses();
            
            if (empty($houses)) {
                return $this->json(['error' => 'House not found'], 404);
            }

            $houseIndex = array_search($id, array_column($houses, 'id'));
            
            if ($houseIndex === false) {
                return $this->json(['error' => 'House not found'], 404);
            }

            if ($this->bookingDataService->isHouseBooked($id)) {
                return $this->json(['error' => 'Cannot delete a booked house'], 400);
            }

            array_splice($houses, $houseIndex, 1);
            
            if (!$this->bookingDataService->saveHouses($houses)) {
                return $this->json(['error' => 'Failed to save changes'], 500);
            }

            return $this->json(['message' => 'House deleted successfully'], 200);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An error occurred while deleting the house'], 500);
        }
    }

    #[Route('/update/{id}', name: 'api_houses_update', methods: ['PUT'])]
    public function updateHouse(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid input'], 400);
        }

        $houses = $this->bookingDataService->getHouses();
        $houseIndex = array_search($id, array_column($houses, 'id'));
        if ($houseIndex === false) {
            return $this->json(['error' => 'House not found'], 404);
        }

        foreach (self::UPDATEABLE_FIELDS as $field) {
            if (isset($data[$field])) {
                $houses[$houseIndex][$field] = $data[$field];
            }
        }
        $this->bookingDataService->saveHouses($houses);
        return $this->json($houses[$houseIndex]);
    }

    #[Route('/create', name: 'api_houses_create', methods: ['POST'])]
    public function createHouse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid input'], 400);
        }
        if (empty($data['name'])) {
            return $this->json(['error' => 'Name is required'], 400);
        }
        if (!isset($data['price'])) {
            return $this->json(['error' => 'Price is required'], 400);
        }
        if (!isset($data['location'])) {
            return $this->json(['error' => 'Location is required'], 400);
        }
        if (!isset($data['description'])) {
            return $this->json(['error' => 'Description is required'], 400);
        }
        if (!isset($data['image'])) {
            return $this->json(['error' => 'Image is required'], 400);
        }

        $houses = $this->bookingDataService->getHouses();
        $ids = array_column($houses, 'id');
        $newId = empty($ids) ? 1 : max($ids) + 1;
        $house = [
            'id' => $newId,
            'name' => $data['name'],
            'price' => $data['price'],
            'location' => $data['location'],
            'description' => $data['description'],
            'image' => $data['image']
        ];
        $houses[] = $house;
        $this->bookingDataService->saveHouses($houses);
        return $this->json($house);
    }

    #[Route('/get/{id}', name: 'api_houses_get', methods: ['GET'])]
    public function getHouse(int $id): JsonResponse
    {
        $houses = $this->bookingDataService->getHouses();
        $houseIndex = array_search($id, array_column($houses, 'id'));
        
        if ($houseIndex === false) {
            return $this->json(['error' => 'House not found'], 404);
        }
        
        return $this->json($houses[$houseIndex]);
    }

    #[Route('/list', name: 'api_houses_get_all', methods: ['GET'])]
    public function getHouses(): JsonResponse
    {
        $houses = $this->bookingDataService->getHouses();
        return $this->json($houses);
    }
} 