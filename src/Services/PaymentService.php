<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Repositories\BookingRepository;
use App\Utils\Response;
use Exception;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;

class PaymentService
{
    protected PaymentRepository $payments;
    protected BookingRepository $bookings;

    public function __construct()
    {
        $this->payments = new PaymentRepository();
        $this->bookings = new BookingRepository();

        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    /**
     * Crear intención de pago para un booking
     */
    public function createIntent(int $bookingId, int $userId): array
    {
        $booking = $this->bookings->findById($bookingId);
        if (!$booking) throw new Exception("Reserva no encontrada.");
        if ($booking['user_id'] !== $userId) throw new Exception("Acceso denegado.");

        if ($booking['status'] !== 'pending')
            throw new Exception("La reserva ya no está pendiente.");

        $amount = (int)($booking['final_amount'] * 100); // Stripe usa centavos

        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            'metadata' => [
                'booking_id' => $bookingId
            ]
        ]);

        return [
            'clientSecret' => $intent->client_secret,
            'paymentIntentId' => $intent->id
        ];
    }

    /**
     * Confirmar pago con Stripe
     */
    public function confirmPayment(int $bookingId, string $paymentIntentId): array
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded')
            throw new Exception("El pago no fue completado.");

        $booking = $this->bookings->findById($bookingId);

        // Crear registro de pago
        $payment = $this->payments->create([
            'booking_id' => $bookingId,
            'amount' => $booking['final_amount'],
            'payment_method' => 'card',
            'transaction_id' => $intent->id,
            'card_last4' => $intent->charges->data[0]->payment_method_details->card->last4,
            'card_brand' => $intent->charges->data[0]->payment_method_details->card->brand,
            'status' => 'completed',
            'paid_at' => date('Y-m-d H:i:s')
        ]);

        // Cambiar estado de reserva
        $this->bookings->updateStatus($bookingId, 'confirmed');

        return $payment;
    }

    /**
     * Reembolso para admin/staff
     */
    public function refund(int $paymentId, string $reason): array
    {
        $payment = $this->payments->find($paymentId);

        if (!$payment) throw new Exception("Pago no encontrado.");
        if ($payment['status'] !== 'completed') throw new Exception("El pago no está disponible para reembolso.");

        // Ejecutar reembolso en Stripe
        Refund::create([
            'payment_intent' => $payment['transaction_id'],
            'reason' => 'requested_by_customer'
        ]);

        // Actualizar BD
        return $this->payments->update($paymentId, [
            'status' => 'refunded',
            'refunded_at' => date('Y-m-d H:i:s'),
            'refund_reason' => $reason
        ]);
    }

    /**
     * Pago en efectivo
     */
    public function payCash(int $bookingId, float $amount): array
    {
        $booking = $this->bookings->findById($bookingId);
        if (!$booking) throw new Exception("Reserva no encontrada.");

        $payment = $this->payments->create([
            'booking_id' => $bookingId,
            'amount' => $amount,
            'payment_method' => 'cash',
            'status' => 'completed',
            'paid_at' => date('Y-m-d H:i:s')
        ]);

        $this->bookings->updateStatus($bookingId, 'confirmed');

        return $payment;
    }

    /**
     * Pago por QR simulado
     */
    public function payQR(int $bookingId): array
    {
        $booking = $this->bookings->findById($bookingId);

        $payment = $this->payments->create([
            'booking_id' => $bookingId,
            'amount' => $booking['final_amount'],
            'payment_method' => 'qr',
            'status' => 'completed',
            'paid_at' => date('Y-m-d H:i:s')
        ]);

        $this->bookings->updateStatus($bookingId, 'confirmed');

        return $payment;
    }
}
