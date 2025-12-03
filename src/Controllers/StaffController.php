<?php

namespace App\Controllers;

use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\SeatService; // o usar directamente el modelo Seat
use App\Utils\Response;

class StaffController extends BaseController
{
    protected BookingService $bookings;
    protected PaymentService $payments;
    protected SeatService $seats;

    public function __construct()
    {
        $this->bookings = new BookingService();
        $this->payments = new PaymentService();
        $this->seats = new SeatService();
    }

    // ==========================
    // VALIDAR CÓDIGO DE RESERVA
    // ==========================

    // GET /api/staff/bookings/validate?code=XXXX
    public function validateBooking()
    {
        $code = $_GET['code'] ?? null;
        if (!$code) {
            return Response::json(['message' => 'Código requerido'], 400);
        }

        $booking = $this->bookings->findByCode($code);

        if (!$booking) {
            return Response::json(['valid' => false, 'message' => 'Reserva no encontrada'], 404);
        }

        return Response::json([
            'valid' => true,
            'booking' => $booking
        ]);
    }

    // ==========================
    // CONFIRMAR PAGO EN EFECTIVO
    // ==========================

    // POST /api/staff/bookings/{id}/confirm-cash
    public function confirmCash($params)
    {
        $bookingId = (int)$params['id'];

        // Asumimos que se paga el monto final
        $booking = $this->bookings->findById($bookingId);
        if (!$booking) {
            return Response::json(['message' => 'Reserva no encontrada'], 404);
        }

        $payment = $this->payments->payCash($bookingId, (float)$booking['final_amount']);

        return Response::json([
            'message' => 'Pago en efectivo registrado',
            'payment' => $payment
        ]);
    }

    // ==========================
    // CAMBIAR ASIENTOS
    // ==========================

    // POST /api/staff/bookings/{id}/change-seats
    public function changeSeats($params)
    {
        $bookingId = (int)$params['id'];
        $data = $this->getJsonInput();
        $newSeatIds = $data['seat_ids'] ?? [];

        try {
            $result = $this->bookings->changeSeatsFromStaff($bookingId, $newSeatIds);
            return Response::json([
                'message' => 'Asientos actualizados',
                'booking' => $result
            ]);
        } catch (\Exception $e) {
            return Response::json(['message' => $e->getMessage()], 400);
        }
    }

    // ==========================
    // PROCESAR REEMBOLSOS
    // ==========================

    // POST /api/staff/payments/{id}/refund
    public function refund($params)
    {
        $data = $this->getJsonInput();
        $reason = $data['reason'] ?? 'Reembolso procesado por staff';

        try {
            $payment = $this->payments->refund((int)$params['id'], $reason);
            return Response::json([
                'message' => 'Reembolso procesado',
                'payment' => $payment
            ]);
        } catch (\Exception $e) {
            return Response::json(['message' => $e->getMessage()], 400);
        }
    }

    // ==========================
    // DISPONIBILIDAD EN TIEMPO REAL
    // ==========================

    // GET /api/staff/showtimes/{id}/seats
    public function showtimeSeats($params)
    {
        $showtimeId = (int)$params['id'];
        return Response::json(
            $this->bookings->getSeatMap($showtimeId) // ya lo tienes en BookingService
        );
    }
}
