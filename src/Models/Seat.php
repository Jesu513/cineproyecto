<?php
// ============================================
// src/Models/Seat.php
// Modelo de asiento
// ============================================

namespace App\Models;

class Seat extends BaseModel
{
    protected string $table = 'seats';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'room_id',
        'row_label',
        'seat_number',
        'seat_type',
        'price_modifier',
        'is_available'
    ];

    protected array $casts = [
        'id' => 'int',
        'room_id' => 'int',
        'seat_number' => 'int',
        'price_modifier' => 'float',
        'is_available' => 'bool',
        'created_at' => 'datetime'
    ];

    /**
     * Obtener asientos por sala
     */
    public function getSeatsByRoom(int $roomId): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('room_id', $roomId)
            ->orderBy('row_label', 'ASC')
            ->orderBy('seat_number', 'ASC')
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Obtener mapa de asientos para una función
     */
    public function getSeatMapForShowtime(int $roomId, int $showtimeId): array
    {
        $sql = "SELECT 
                s.*,
                CASE 
                    WHEN bs.id IS NOT NULL THEN 'occupied'
                    WHEN s.is_available = false THEN 'unavailable'
                    ELSE 'available'
                END as status
                FROM {$this->table} s
                LEFT JOIN booking_seats bs ON s.id = bs.seat_id
                LEFT JOIN bookings b ON bs.booking_id = b.id 
                    AND b.showtime_id = ?
                    AND b.status IN ('confirmed', 'pending')
                WHERE s.room_id = ?
                ORDER BY s.row_label ASC, s.seat_number ASC";

        return $this->connection->fetchAll($sql, [$showtimeId, $roomId]);
    }

    /**
     * Verificar disponibilidad de asiento para función
     */
    public function isSeatAvailableForShowtime(int $seatId, int $showtimeId): bool
    {
        $sql = "SELECT COUNT(*) as count
                FROM booking_seats bs
                JOIN bookings b ON bs.booking_id = b.id
                WHERE bs.seat_id = ?
                AND b.showtime_id = ?
                AND b.status IN ('confirmed', 'pending')";

        $result = $this->connection->fetchOne($sql, [$seatId, $showtimeId]);
        return ($result['count'] ?? 0) === 0;
    }

    /**
     * Verificar disponibilidad de múltiples asientos
     */
    public function areSeatsAvailableForShowtime(array $seatIds, int $showtimeId): array
    {
        if (empty($seatIds)) {
            return ['available' => true, 'unavailable_seats' => []];
        }

        $placeholders = implode(',', array_fill(0, count($seatIds), '?'));
        $params = array_merge($seatIds, [$showtimeId]);

        $sql = "SELECT bs.seat_id
                FROM booking_seats bs
                JOIN bookings b ON bs.booking_id = b.id
                WHERE bs.seat_id IN ({$placeholders})
                AND b.showtime_id = ?
                AND b.status IN ('confirmed', 'pending')";

        $unavailable = $this->connection->fetchAll($sql, $params);
        $unavailableIds = array_column($unavailable, 'seat_id');

        return [
            'available' => empty($unavailableIds),
            'unavailable_seats' => $unavailableIds
        ];
    }

    /**
     * Buscar asiento específico
     */
    public function findSeat(int $roomId, string $rowLabel, int $seatNumber): ?array
    {
        $result = $this->queryBuilder
            ->table($this->table)
            ->where('room_id', $roomId)
            ->where('row_label', $rowLabel)
            ->where('seat_number', $seatNumber)
            ->first();

        return $result ? $this->hideAttributes($result) : null;
    }

    /**
     * Obtener asientos por tipo
     */
    public function getSeatsByType(int $roomId, string $type): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('room_id', $roomId)
            ->where('seat_type', $type)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Contar asientos por tipo en una sala
     */
    public function countSeatsByType(int $roomId): array
    {
        $sql = "SELECT seat_type, COUNT(*) as count
                FROM {$this->table}
                WHERE room_id = ?
                GROUP BY seat_type";

        $results = $this->connection->fetchAll($sql, [$roomId]);
        
        $counts = [
            'standard' => 0,
            'vip' => 0,
            'disabled' => 0
        ];

        foreach ($results as $row) {
            $counts[$row['seat_type']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Crear asientos en lote para una sala
     */
    public function createSeatsForRoom(int $roomId, int $rows, int $columns, array $options = []): int
    {
        $rowLabels = range('A', chr(64 + $rows)); // A, B, C, etc.
        $createdCount = 0;

        $this->beginTransaction();

        try {
            foreach ($rowLabels as $rowLabel) {
                for ($seatNum = 1; $seatNum <= $columns; $seatNum++) {
                    // Determinar tipo de asiento
                    $seatType = $options['seat_type'] ?? 'standard';
                    $priceModifier = $options['price_modifier'] ?? 1.00;

                    // VIP en primeras filas si está configurado
                    if (!empty($options['vip_rows']) && 
                        in_array($rowLabel, $options['vip_rows'])) {
                        $seatType = 'vip';
                        $priceModifier = 1.50;
                    }

                    $this->create([
                        'room_id' => $roomId,
                        'row_label' => $rowLabel,
                        'seat_number' => $seatNum,
                        'seat_type' => $seatType,
                        'price_modifier' => $priceModifier,
                        'is_available' => true
                    ]);

                    $createdCount++;
                }
            }

            $this->commit();
            return $createdCount;

        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar todos los asientos de una sala
     */
    public function deleteRoomSeats(int $roomId): int
    {
        return $this->queryBuilder
            ->table($this->table)
            ->where('room_id', $roomId)
            ->delete();
    }

    /**
     * Actualizar disponibilidad de asiento
     */
    public function toggleAvailability(int $seatId, bool $isAvailable): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $seatId)
            ->update(['is_available' => $isAvailable]);

        return $affected > 0;
    }

    /**
     * Obtener asientos ocupados para una función
     */
    public function getOccupiedSeatsForShowtime(int $showtimeId): array
    {
        $sql = "SELECT s.*, bs.booking_id, b.booking_code, b.status as booking_status
                FROM {$this->table} s
                JOIN booking_seats bs ON s.id = bs.seat_id
                JOIN bookings b ON bs.booking_id = b.id
                WHERE b.showtime_id = ?
                AND b.status IN ('confirmed', 'pending')
                ORDER BY s.row_label ASC, s.seat_number ASC";

        return $this->connection->fetchAll($sql, [$showtimeId]);
    }
}