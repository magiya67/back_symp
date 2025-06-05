<?php

namespace App\Controller;

use App\Service\BookingDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/bookings')]
final class BookingController extends AbstractController
{
    public function __construct(
        private readonly BookingDataService $bookingDataService
    ) {
    }

    #[Route('', name: 'api_booking_create', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['phone'], $data['house_id'])) {
            return $this->json(['error' => 'phone and house_id are required'], 400);
        }

        $houseId = (int)$data['house_id'];
        if (!$this->bookingDataService->houseExists($houseId)) {
            return $this->json(['error' => 'House not found'], 404);
        }

        if ($this->bookingDataService->isHouseBooked($houseId)) {
            return $this->json(['error' => 'House is already booked'], 409);
        }

        $bookings = $this->bookingDataService->getBookings();
        $id = count($bookings) + 1;
        
        $booking = [
            'id' => $id,
            'phone' => $data['phone'],
            'house_id' => $houseId,
            'comment' => $data['comment'] ?? '',
            'created_at' => date('c'),
        ];

        $this->bookingDataService->createBooking($booking);
        return $this->json(['success' => true, 'booking' => $booking]);
    }

    #[Route('/{id}', name: 'api_booking_update', methods: ['PUT'])]
    public function updateBooking(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['comment'])) {
            return $this->json(['error' => 'comment is required'], 400);
        }

        $success = $this->bookingDataService->updateBooking($id, ['comment' => $data['comment']]);
        if (!$success) {
            return $this->json(['error' => 'Booking not found'], 404);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/{id}', name: 'api_bookings_delete', methods: ['DELETE'])]
    public function deleteBooking(int $id): JsonResponse
    {
        try {
            $bookings = $this->bookingDataService->getBookings();
            $bookingIndex = null;
            foreach ($bookings as $index => $booking) {
                if ($booking['id'] == $id) {
                    $bookingIndex = $index;
                    break;
                }
            }
            if ($bookingIndex === null) {
                return $this->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
            }
            array_splice($bookings, $bookingIndex, 1);
            $this->bookingDataService->saveBookings($bookings);
            return $this->json(['message' => 'Booking deleted successfully']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete booking'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'api_booking_get', methods: ['GET'])]
    public function getBooking(int $id): JsonResponse
    {
        $bookings = $this->bookingDataService->getBookings();
        foreach ($bookings as $booking) {
            if ((int)$booking['id'] === $id) {
                return $this->json($booking);
            }
        }
        return $this->json(['error' => 'Booking not found'], Response::HTTP_NOT_FOUND);
    }

    #[Route('', name: 'api_bookings_list', methods: ['GET'])]
    public function listBookings(): JsonResponse
    {
        $bookings = $this->bookingDataService->getBookings();
        return $this->json($bookings);
    }
} 