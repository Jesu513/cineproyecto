<?php
// ============================================
// src/Repositories/SeatRepository.php
// Repositorio de asientos
// ============================================

namespace App\Repositories;

use App\Models\Seat;

class SeatRepository extends BaseRepository
{
    protected Seat $seatModel;

    public function __construct()
    {
        $this->seatModel = new Seat();
        parent::__construct($this->seatModel);
    }

    /**
     * Obtener asientos por sala
     */
    public function getSeatsByRoom(int $roomId): array
    {
        try {
            return $this->seatModel->getSeatsByRoom($roomId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['room_id' => $roomId]);
            throw $e;
        }
    }

    /**
     * Obtener mapa de asientos para función
     */
    public function getSeatMapForShowtime(int $roomId, int $showtimeId): array
    {
        try {
            $seats = $this->seatModel->getSeatMapForShowtime($roomId, $showtimeId);

            // Organizar por filas
            $seatMap = [];
            foreach ($seats as $seat) {
                $row = $seat['row_label'];
                if (!isset($seatMap[$row])) {
                    $seatMap[$row] = [];
                }
                $seatMap[$row][] = $seat;
            }

            return $seatMap;

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'room_id' => $roomId,
                'showtime_id' => $showtimeId
            ]);
            throw $e;
        }
    }

    /**
     * Verificar disponibilidad de asiento
     */
    public function isSeatAvailableForShowtime(int $seatId, int $showtimeId): bool
    {
        try {
            return $this->seatModel->isSeatAvailableForShowtime($seatId, $showtimeId);
        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'seat_id' => $seatId,
                'showtime_id' => $showtimeId
            ]);
            throw $e;
        }
    }

    /**
     * Verificar disponibilidad de múltiples asientos
     */
    public function areSeatsAvailableForShowtime(array $seatIds, int $showtimeId): array
    {
        try {
            return $this->seatModel->areSeatsAvailableForShowtime($seatIds, $showtimeId);
        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'seat_ids' => $seatIds,
                'showtime_id' => $showtimeId
            ]);
            throw $e;
        }
    }

    /**
     * Buscar asiento específico
     */
    public function findSeat(int $roomId, string $rowLabel, int $seatNumber): ?array
    {
        try {
            return $this->seatModel->findSeat($roomId, $rowLabel, $seatNumber);
        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'room_id' => $roomId,
                'row' => $rowLabel,
                'seat' => $seatNumber
            ]);
            throw $e;
        }
    }

    /**
     * Obtener asientos por tipo
     */
    public function getSeatsByType(int $roomId, string $type): array
    {
        try {
            return $this->seatModel->getSeatsByType($roomId, $type);
        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'room_id' => $roomId,
                'type' => $type
            ]);
            throw $e;
        }
    }

    /**
     * Contar asientos por tipo
     */
    public function countSeatsByType(int $roomId): array
    {
        try {
            return $this->seatModel->countSeatsByType($roomId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['room_id' => $roomId]);
            throw $e;
        }
    }

    /**
     * Actualizar disponibilidad
     */
    public function toggleAvailability(int $seatId, bool $isAvailable): bool
    {
        try {
            $this->logger->info('Toggle seat availability', [
                'seat_id' => $seatId,
                'is_available' => $isAvailable
            ]);
            return $this->seatModel->toggleAvailability($seatId, $isAvailable);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['seat_id' => $seatId]);
            throw $e;
        }
    }

    /**
     * Obtener asientos ocupados
     */
    public function getOccupiedSeatsForShowtime(int $showtimeId): array
    {
        try {
            return $this->seatModel->getOccupiedSeatsForShowtime($showtimeId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $showtimeId]);
            throw $e;
        }
    }

    /**
     * Obtener información detallada de asientos por IDs
     */
    public function getSeatsDetails(array $seatIds): array
    {
        try {
            if (empty($seatIds)) {
                return [];
            }

            $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
            $sql = "SELECT * FROM seats WHERE id IN ({$placeholders}) ORDER BY row_label ASC, seat_number ASC";

            return $this->seatModel->connection->fetchAll($sql, $seatIds);

        } catch (\Exception $e) {
            $this->logger->exception($e, ['seat_ids' => $seatIds]);
            throw $e;
        }
    }
}