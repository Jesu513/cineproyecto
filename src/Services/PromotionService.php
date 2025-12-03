<?php

namespace App\Services;

use App\Repositories\PromotionRepository;
use App\Repositories\BookingRepository;
use Exception;

class PromotionService
{
    protected PromotionRepository $promotions;
    protected BookingRepository $bookings;

    public function __construct()
    {
        $this->promotions = new PromotionRepository();
        $this->bookings = new BookingRepository();
    }

    /**
     * Validar cupón y calcular descuento
     */
    public function validateCoupon(string $code, int $userId, int $bookingId): array
    {
        $promotion = $this->promotions->findByCode($code);
        if (!$promotion) throw new Exception("Cupón inválido.");

        if (!$promotion['is_active']) throw new Exception("El cupón no está activo.");

        // Validar fechas
        $today = date('Y-m-d');
        if ($today < $promotion['valid_from'] || $today > $promotion['valid_until'])
            throw new Exception("El cupón no está disponible en estas fechas.");

        $booking = $this->bookings->findById($bookingId);
        if (!$booking) throw new Exception("Reserva inválida.");

        // Validar número mínimo de tickets
        if ($booking['total_seats'] < $promotion['min_tickets'])
            throw new Exception("Se requieren mínimo {$promotion['min_tickets']} boletos.");

        // Validar días aplicables (1 = Lunes ... 7 = Domingo)
        $weekday = date('N');
        if (!empty($promotion['applicable_days']) && !in_array($weekday, $promotion['applicable_days']))
            throw new Exception("Este cupón no aplica hoy.");

        // Validar películas aplicables
        if (!empty($promotion['applicable_movies']) &&
            !in_array($booking['movie_id'], $promotion['applicable_movies']))
            throw new Exception("Este cupón no aplica para esta película.");

        // Validación: 2x1
        if ($promotion['discount_type'] === '2x1') {
            if ($booking['total_seats'] < 2)
                throw new Exception("Debes comprar mínimo 2 entradas para usar 2x1.");

            $discount = $booking['total_amount'] / 2;
        }

        // Descuento fijo
        elseif ($promotion['discount_type'] === 'fixed') {
            $discount = min($promotion['discount_value'], $booking['total_amount']);
        }

        // Porcentaje
        elseif ($promotion['discount_type'] === 'percentage') {
            $discount = ($booking['total_amount'] * $promotion['discount_value'] / 100);
            if ($promotion['max_discount'])
                $discount = min($discount, $promotion['max_discount']);
        }

        else {
            throw new Exception("Tipo de promoción desconocido.");
        }

        return [
            'valid' => true,
            'promotion_id' => $promotion['id'],
            'discount' => round($discount, 2),
            'final_amount' => round($booking['total_amount'] - $discount, 2)
        ];
    }

    /**
     * Aplicar el cupón a la reserva
     */
    public function applyCoupon(int $bookingId, int $promotionId, float $discount)
    {
        $booking = $this->bookings->findById($bookingId);

        $updated = $this->bookings->update($bookingId, [
            'discount_amount' => $discount,
            'final_amount' => max(0, $booking['total_amount'] - $discount)
        ]);

        // Aumentar contador de usos
        $this->promotions->updateUsage($promotionId);

        return $updated;
    }
}
