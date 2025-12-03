<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    protected Notification $model;
    protected EmailService $email;
    protected PDFService $pdf;

    public function __construct()
    {
        $this->model = new Notification();
        $this->email = new EmailService();
        $this->pdf = new PDFService();
    }

    /**
     * Registrar notificación en BD
     */
    public function push(int $userId, string $type, string $title, string $message, array $data = [])
    {
        return $this->model->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false
        ]);
    }

    /**
     * Confirmación de reserva (PDF + email)
     */
    public function sendBookingConfirmation(array $booking, array $showtime, array $movie, array $seats, string $email)
    {
        $ticketPath = $this->pdf->generateTicket($booking, $showtime, $movie, $seats);

        $subject = "🎟 Confirmación de Reserva – {$movie['title']}";
        $html = "
            <h2>Tu reserva fue confirmada</h2>
            <p>Gracias por reservar con SisCine.</p>
            <p><strong>Código:</strong> {$booking['booking_code']}</p>
        ";

        $this->email->sendEmail($email, $subject, $html, $ticketPath);

        $this->push(
            $booking['user_id'],
            'booking_confirmed',
            "Reserva Confirmada",
            "Tu reserva para {$movie['title']} fue confirmada.",
            ['booking_id' => $booking['id']]
        );
    }

    /**
     * Cancelación de reserva
     */
    public function sendBookingCancellation(array $booking, string $email)
    {
        $subject = "❌ Reserva Cancelada";
        $html = "
            <p>Tu reserva con código {$booking['booking_code']} ha sido cancelada.</p>
        ";

        $this->email->sendEmail($email, $subject, $html);

        $this->push(
            $booking['user_id'],
            'booking_cancelled',
            "Reserva Cancelada",
            "Tu reserva fue cancelada.",
            ['booking_id' => $booking['id']]
        );
    }

    /**
     * Recordatorio de función — se ejecuta con CRON
     */
    public function sendShowtimeReminder(array $booking, array $movie, string $email)
    {
        $subject = "⏰ Recordatorio de tu función";
        $html = "
            <p>No olvides tu función:</p>
            <p><strong>{$movie['title']}</strong></p>
        ";

        $this->email->sendEmail($email, $subject, $html);

        $this->push(
            $booking['user_id'],
            'booking_reminder',
            "Recordatorio de Función",
            "Tu función está por comenzar.",
            ['booking_id' => $booking['id']]
        );
    }

    /**
     * Nueva película
     */
    public function sendNewMovieNotification(int $userId, array $movie)
    {
        $this->push(
            $userId,
            'new_movie',
            "Nueva película disponible",
            "{$movie['title']} ya está en cartelera.",
            ['movie_id' => $movie['id']]
        );
    }

    /**
     * Promociones activas
     */
    public function sendPromotionNotification(int $userId, array $promotion)
    {
        $this->push(
            $userId,
            'promotion',
            "Nueva promoción disponible",
            $promotion['description'],
            ['promotion_id' => $promotion['id']]
        );
    }
}
