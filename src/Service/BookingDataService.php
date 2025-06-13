<?php

namespace App\Service;

class BookingDataService
{
    private string $housesFile;
    private string $bookingsFile;

    public function __construct(string $housesFile, string $bookingsFile)
    {
        $this->housesFile = $housesFile;
        $this->bookingsFile = $bookingsFile;
    }

    public function getHouses(): array
    {
        if (!file_exists($this->housesFile)) {
            return [];
        }

        $houses = [];
        if (($handle = fopen($this->housesFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                $houses[] = array_combine($headers, $data);
            }
            fclose($handle);
        }
        return $houses;
    }

    public function getBookings(): array
    {
        if (!file_exists($this->bookingsFile)) {
            return [];
        }

        $bookings = [];
        if (($handle = fopen($this->bookingsFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle, 0, ',', '"', '\\');
            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                $bookings[] = array_combine($headers, $data);
            }
            fclose($handle);
        }
        return $bookings;
    }

    public function saveHouses(array $houses): bool
    {
        try {
            if (($handle = fopen($this->housesFile, "w")) !== FALSE) {
                // Пишем заголовок
                $header = ['id', 'name', 'price', 'location', 'description', 'image'];
                fputcsv($handle, $header, ',', '"', '\\');
                foreach ($houses as $house) {
                    fputcsv($handle, $house, ',', '"', '\\');
                }
                fclose($handle);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function saveBookings(array $bookings): bool
    {
        try {
            if (($handle = fopen($this->bookingsFile, "w")) !== FALSE) {
                // Всегда пишем заголовок
                $header = ['id', 'phone', 'house_id', 'comment', 'created_at'];
                fputcsv($handle, $header, ',', '"', '\\');
                foreach ($bookings as $booking) {
                    fputcsv($handle, $booking, ',', '"', '\\');
                }
                fclose($handle);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function houseExists(int $houseId): bool
    {
        $houses = $this->getHouses();
        foreach ($houses as $house) {
            if ((int)$house['id'] === $houseId) {
                return true;
            }
        }
        return false;
    }

    public function isHouseBooked(int $houseId): bool
    {
        $bookings = $this->getBookings();
        foreach ($bookings as $booking) {
            if ((int)$booking['house_id'] === $houseId) {
                return true;
            }
        }
        return false;
    }

    public function createBooking(array $booking): void
    {
        $bookings = $this->getBookings();
        $bookings[] = $booking;
        
        if (($handle = fopen($this->bookingsFile, "w")) !== FALSE) {
            fputcsv($handle, array_keys($booking), ',', '"', '\\');
            foreach ($bookings as $booking) {
                fputcsv($handle, $booking, ',', '"', '\\');
            }
            fclose($handle);
        }
    }

    public function updateBooking(int $id, array $data): bool
    {
        $bookings = $this->getBookings();
        $found = false;
        
        foreach ($bookings as &$booking) {
            if ((int)$booking['id'] === $id) {
                $booking = array_merge($booking, $data);
                $found = true;
                break;
            }
        }

        if (!$found) {
            return false;
        }

        if (($handle = fopen($this->bookingsFile, "w")) !== FALSE) {
            fputcsv($handle, array_keys($bookings[0]), ',', '"', '\\');
            foreach ($bookings as $booking) {
                fputcsv($handle, $booking, ',', '"', '\\');
            }
            fclose($handle);
        }

        return true;
    }

    public function deleteBooking(int $id): bool
    {
        $bookings = $this->getBookings();
        $newBookings = array_filter($bookings, function($booking) use ($id) {
            return (int)$booking['id'] !== $id;
        });

        if (count($newBookings) === count($bookings)) {
            return false;
        }

        if (($handle = fopen($this->bookingsFile, "w")) !== FALSE) {
            if (!empty($newBookings)) {
                fputcsv($handle, array_keys(reset($newBookings)), ',', '"', '\\');
                foreach ($newBookings as $booking) {
                    fputcsv($handle, $booking, ',', '"', '\\');
                }
            }
            fclose($handle);
        }

        return true;
    }
} 