<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

use function count;

use const DATE_ATOM;

/** @psalm-suppress UnusedClass */
#[Route('/api/bookings')]
final class BookingController extends AbstractController
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly HouseRepository $houseRepository,
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'api_booking_create', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (! isset($data['phone'], $data['house_id'])) {
            return $this->json(['error' => 'phone and house_id are required'], 400);
        }
        $house = $this->houseRepository->find((int) $data['house_id']);
        if (! $house) {
            return $this->json(['error' => 'House not found'], 404);
        }
        // Проверка, что дом не забронирован
        $existing = $this->bookingRepository->findBy(['house' => $house]);
        if (count($existing) > 0) {
            return $this->json(['error' => 'House is already booked'], 409);
        }
        $booking = new Booking();
        $booking->setHouse($house)
            ->setPhone($data['phone'])
            ->setMessage($data['comment'] ?? '')
            ->setName($data['name'] ?? null)
            ->setCreatedAt(new DateTimeImmutable());
        $this->bookingRepository->save($booking, true);

        return $this->json(['success' => true, 'booking' => $this->serializeBooking($booking)]);
    }

    #[Route('/{id}', name: 'api_booking_update', methods: ['PUT'])]
    public function updateBooking(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $booking = $this->bookingRepository->find($id);
        if (! $booking) {
            return $this->json(['error' => 'Booking not found'], 404);
        }
        if (isset($data['phone'])) {
            $booking->setPhone($data['phone']);
        }
        if (isset($data['comment'])) {
            $booking->setMessage($data['comment']);
        }
        if (isset($data['name'])) {
            $booking->setName($data['name']);
        }
        $this->em->flush();

        return $this->json($this->serializeBooking($booking));
    }

    #[Route('/{id}', name: 'api_booking_delete', methods: ['DELETE'])]
    public function deleteBooking(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);
        if (! $booking) {
            return $this->json(['error' => 'Booking not found'], 404);
        }
        $this->bookingRepository->remove($booking, true);

        return $this->json(['message' => 'Booking deleted successfully']);
    }

    #[Route('/{id}', name: 'api_booking_get', methods: ['GET'])]
    public function getBooking(int $id): JsonResponse
    {
        $booking = $this->bookingRepository->find($id);
        if (! $booking) {
            return $this->json(['error' => 'Booking not found'], 404);
        }

        return $this->json($this->serializeBooking($booking));
    }

    #[Route('', name: 'api_booking_get_all', methods: ['GET'])]
    public function getBookings(): JsonResponse
    {
        $bookings = $this->bookingRepository->findAll();

        return $this->json(array_map(fn ($b) => $this->serializeBooking($b), $bookings));
    }

    private function serializeBooking(Booking $booking): array
    {
        return [
            'id' => $booking->getId(),
            'house_id' => $booking->getHouse()?->getId(),
            'phone' => $booking->getPhone(),
            'comment' => $booking->getMessage(),
            'name' => $booking->getName(),
            'created_at' => $booking->getCreatedAt()?->format(DATE_ATOM),
        ];
    }

    // Старая файловая логика была реализована через BookingDataService
    // Пример:
    // $bookings = $this->bookingDataService->getBookings();
    // $this->bookingDataService->createBooking($booking);
}
