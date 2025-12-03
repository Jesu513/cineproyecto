<?php

namespace App\Services;

use App\Repositories\BookingRepository;
use App\Repositories\ShowtimeRepository;
use App\Repositories\SeatRepository;
use Exception;

class BookingService
{
    protected BookingRepository $bookings;
    protected ShowtimeRepository $showtimes;
    protected SeatRepository $seats;

    public function __construct()
    {
        $this->bookings = new BookingRepository();
        $this->showtimes = new ShowtimeRepository();
        $this->seats = new SeatRepository();
    }

    /**
     * Obtener mapa de asientos
     */
    public function getSeatMap(int $showtimeId): array
    {
        $showtime = $this->showtimes->find($showtimeId);
        if (!$showtime) throw new Exception("La función no existe.");

        $roomId = $showtime['room_id'];

        // Lista completa de asientos de la sala
        $allSeats = $this->seats->getSeatsByRoom($roomId);

        // Asientos reservados o confirmados
        $reserved = $this->bookings->getReservedSeats($showtimeId);

        $occupiedIds = array_column($reserved, 'seat_id');

        foreach ($allSeats as &$seat) {
            $seat['status'] = in_array($seat['id'], $occupiedIds)
                ? 'occupied'
                : ($seat['is_available'] ? 'available' : 'unavailable');
        }

        return $allSeats;
    }

    /**
     * Crear reserva temporal
     */
    public function createTemporaryReservation(
        int $userId,
        int $showtimeId,
        array $seatIds
    ): array {
        // Validar disponibilidad
        $availability = $this->seats->areSeatsAvailableForShowtime($seatIds, $showtimeId);

        if (!$availability['available']) {
            throw new Exception("Algunos asientos ya no están disponibles.");
        }

        // Obtener precio base showtime
        $showtime = $this->showtimes->find($showtimeId);
        if (!$showtime) throw new Exception("Showtime no encontrado.");

        $price = (float)$showtime['base_price'];
        $total = $price * count($seatIds);

        // Generar código único
        $code = strtoupper(bin2hex(random_bytes(4)));

        // Crear reserva
        $booking = $this->bookings->createBooking([
            'user_id' => $userId,
            'showtime_id' => $showtimeId,
            'booking_code' => $code,
            'total_seats' => count($seatIds),
            'total_amount' => $total,
            'final_amount' => $total,
            'status' => 'pending',
            'reserved_until' => date('Y-m-d H:i:s', time() + 600)
        ]);

        // Asignar asientos
        foreach ($seatIds as $seatId) {
            $this->bookings->addSeat($booking['id'], $seatId, $price);
        }

        return [
            'booking_id' => $booking['id'],
            'booking_code' => $code,
            'expires_at' => time() + 600
        ];
    }

    /**
     * Confirmar reserva
     */
    public function confirmBooking(int $bookingId, int $userId): array
    {
        $booking = $this->bookings->findById($bookingId);

        if (!$booking) throw new Exception("Reserva no encontrada.");
        if ($booking['user_id'] !== $userId) throw new Exception("Acceso denegado.");

        if ($booking['status'] !== 'pending')
            throw new Exception("La reserva ya fue confirmada o cancelada.");

        if (strtotime($booking['reserved_until']) < time()) {
            $this->bookings->updateStatus($bookingId, 'expired');
            throw new Exception("La reserva expiró.");
        }

        return $this->bookings->updateStatus($bookingId, 'confirmed');
    }

    /**
     * Cancelar reserva
     */
    public function cancelBooking(int $bookingId, int $userId): bool
    {
        $booking = $this->bookings->findById($bookingId);

        if (!$booking) throw new Exception("Reserva no encontrada.");
        if ($booking['user_id'] !== $userId) throw new Exception("Acceso denegado.");

        $this->bookings->updateStatus($bookingId, 'cancelled');
        $this->bookings->removeSeats($bookingId);

        return true;
    }

    /**
     * Mis reservas
     */
    public function getUserBookings(int $userId): array
    {
        return $this->bookings->getUserBookings($userId);
    }
}
