<?php
// ============================================
// SeatController
// Manejo de asientos del cine
// ============================================

namespace App\Controllers;

use App\Models\Seat;
use App\Models\Showtime;
use App\Utils\Response;

class SeatController extends BaseController
{
    private Seat $seatModel;
    private Showtime $showtimeModel;

    public function __construct()
    {
        $this->seatModel = new Seat();
        $this->showtimeModel = new Showtime();
    }

    /**
     * GET /api/showtimes/{id}/seats
     * Obtener el mapa de asientos para un horario
     */
    public function getSeatMap($showtimeId)
    {
        $showtime = $this->showtimeModel->find((int)$showtimeId);

        if (!$showtime) {
            return $this->error("Horario no encontrado", 404);
        }

        $roomId = $showtime['room_id'];

        $seatMap = $this->seatModel->getSeatMapForShowtime($roomId, $showtimeId);

        return $this->success($seatMap, "Mapa de asientos cargado");
    }

    /**
     * GET /api/showtimes/{id}/occupied-seats
     */
    public function getOccupiedSeats($showtimeId)
    {
        $occupied = $this->seatModel->getOccupiedSeatsForShowtime($showtimeId);

        return $this->success($occupied, "Asientos ocupados cargados");
    }
}
