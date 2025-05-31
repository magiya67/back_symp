<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class BookingController extends AbstractController
{
    #[Route('/booking', name: 'app_booking')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/BookingController.php',
        ]);
    }

    #[Route('/api/houses/free', name: 'api_houses_free', methods: ['GET'])]
    public function freeHouses(): JsonResponse
    {
        $houses = [
            [
                'id' => 1,
                'amenities' => 'никаких',
                'beds' => 2,
                'sea_row' => 1,
            ],
            [
                'id' => 2,
                'amenities' => 'санузел',
                'beds' => 4,
                'sea_row' => 2,
            ],
            [
                'id' => 3,
                'amenities' => 'душевая кабина',
                'beds' => 3,
                'sea_row' => 1,
            ],
        ];
        return $this->json($houses);
    }

    #[Route('/api/booking', name: 'api_booking_create', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['phone'], $data['house_id'])) {
            return $this->json(['error' => 'phone and house_id are required'], 400);
        }
        $file = $this->getParameter('kernel.project_dir') . '/var/bookings.json';
        $bookings = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        $id = count($bookings) + 1;
        $booking = [
            'id' => $id,
            'phone' => $data['phone'],
            'house_id' => $data['house_id'],
            'comment' => $data['comment'] ?? '',
            'created_at' => date('c'),
        ];
        $bookings[] = $booking;
        file_put_contents($file, json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $this->json(['success' => true, 'booking' => $booking]);
    }

    #[Route('/api/booking/{id}', name: 'api_booking_update', methods: ['PUT'])]
    public function updateBooking(int $id, Request $request): JsonResponse
    {
        $file = $this->getParameter('kernel.project_dir') . '/var/bookings.json';
        $bookings = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        $found = false;
        foreach ($bookings as &$booking) {
            if ($booking['id'] == $id) {
                $data = json_decode($request->getContent(), true);
                $booking['comment'] = $data['comment'] ?? $booking['comment'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            return $this->json(['error' => 'Booking not found'], 404);
        }
        file_put_contents($file, json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $this->json(['success' => true, 'booking' => $booking]);
    }

    #[Route('/api/booking/{id}/add', name: 'api_booking_add_put', methods: ['PUT'])]
    public function addBookingPut(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['phone'], $data['house_id'])) {
            return $this->json(['error' => 'phone and house_id are required'], 400);
        }
        $file = $this->getParameter('kernel.project_dir') . '/var/bookings.json';
        $bookings = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        foreach ($bookings as $booking) {
            if ($booking['id'] == $id) {
                return $this->json(['error' => 'Booking with this id already exists'], 409);
            }
        }
        $booking = [
            'id' => $id,
            'phone' => $data['phone'],
            'house_id' => $data['house_id'],
            'comment' => $data['comment'] ?? '',
            'created_at' => date('c'),
        ];
        $bookings[] = $booking;
        file_put_contents($file, json_encode($bookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $this->json(['success' => true, 'booking' => $booking]);
    }

    #[Route('/api/booking/{id}/delete', name: 'api_booking_delete_put', methods: ['PUT'])]
    public function deleteBookingPut(int $id): JsonResponse
    {
        $file = $this->getParameter('kernel.project_dir') . '/var/bookings.json';
        $bookings = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
        $newBookings = [];
        $found = false;
        foreach ($bookings as $booking) {
            if ($booking['id'] == $id) {
                $found = true;
                continue;
            }
            $newBookings[] = $booking;
        }
        if (!$found) {
            return $this->json(['error' => 'Booking not found'], 404);
        }
        file_put_contents($file, json_encode($newBookings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $this->json(['success' => true, 'deleted_id' => $id]);
    }
}
