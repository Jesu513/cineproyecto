<?php

namespace App\Services;

use TCPDF;

class PDFService
{
    public function generateTicket(array $booking, array $showtime, array $movie, array $seats): string
    {
        $pdf = new TCPDF();
        $pdf->SetCreator("SisCine");
        $pdf->SetAuthor("SisCine");
        $pdf->SetTitle("Ticket de Reserva {$booking['booking_code']}");
        $pdf->AddPage();

        $html = "
        <h1>🎟 Ticket de Reserva</h1>
        <p><strong>Código:</strong> {$booking['booking_code']}</p>
        <p><strong>Película:</strong> {$movie['title']}</p>
        <p><strong>Fecha:</strong> {$showtime['show_date']} - {$showtime['show_time']}</p>
        <p><strong>Sala:</strong> {$showtime['room_id']}</p>
        <p><strong>Asientos:</strong> " . implode(', ', $seats) . "</p>
        <p><strong>Total pagado:</strong> {$booking['final_amount']} USD</p>
        ";

        $pdf->writeHTML($html);

        // Guardar archivo
        $filename = storage_path("tickets/{$booking['booking_code']}.pdf");
        $pdf->Output($filename, 'F');

        return $filename;
    }
}
