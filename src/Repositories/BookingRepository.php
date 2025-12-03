<?php

namespace App\Repositories;

use App\Models\Booking;
use App\Models\BookingSeat;
use App\Database\Connection;

class BookingRepository
{
    protected Booking $booking;
    protected BookingSeat $bookingSeat;
    protected Connection $db;

    public function __construct()
    {
        $this->booking = new Booking();
        $this->bookingSeat = new BookingSeat();
        $this->db = Connection::getInstance();
    }

    /**
     * Crear reserva
     */
    public function createBooking(array $data): array
    {
        return $this->booking->create($data);
    }

    /**
     * Buscar reserva por ID
     */
    public function findById(int $id): ?array
    {
        return $this->booking->find($id);
    }

    /**
     * Buscar por booking_code
     */
    public function findByCode(string $code): ?array
    {
        return $this->booking->findBy('booking_code', $code);
    }

    /**
     * Asignar asiento
     */
    public function addSeat(int $bookingId, int $seatId, float $price)
    {
        return $this->bookingSeat->create([
            'booking_id' => $bookingId,
            'seat_id' => $seatId,
            'price' => $price
        ]);
    }

    /**
     * Obtener asientos reservados para un showtime
     */
    public function getReservedSeats(int $showtimeId): array
    {
        $sql = "
            SELECT bs.seat_id, b.status
            FROM booking_seats bs
            JOIN bookings b ON bs.booking_id = b.id
            WHERE b.showtime_id = ?
            AND b.status IN ('pending', 'confirmed')
            AND (b.reserved_until IS NULL OR b.reserved_until > NOW());
        ";

        return $this->db->fetchAll($sql, [$showtimeId]);
    }

    /**
     * Actualizar estado
     */
    public function updateStatus(int $id, string $status): ?array
    {
        return $this->booking->update($id, ['status' => $status]);
    }

    /**
     * Reservas de usuario
     */
    public function getUserBookings(int $userId): array
    {
        return $this->booking->findAllBy('user_id', $userId);
    }

    /**
     * Eliminar seats de reserva
     */
    public function removeSeats(int $bookingId)
    {
        return $this->bookingSeat
            ->query()
            ->where('booking_id', $bookingId)
            ->delete();
    }
}
