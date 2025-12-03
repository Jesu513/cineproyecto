<?php
// ============================================
// src/Models/Showtime.php
// Modelo de horario de función
// ============================================

namespace App\Models;

class Showtime extends BaseModel
{
    protected string $table = 'showtimes';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'movie_id',
        'room_id',
        'show_date',
        'show_time',
        'base_price',
        'available_seats',
        'is_active'
    ];

    protected array $casts = [
        'id' => 'int',
        'movie_id' => 'int',
        'room_id' => 'int',
        'base_price' => 'float',
        'available_seats' => 'int',
        'is_active' => 'bool',
        'show_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Obtener funciones activas
     */
    public function getActiveShowtimes(): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->where('show_date', '>=', date('Y-m-d'))
            ->orderBy('show_date', 'ASC')
            ->orderBy('show_time', 'ASC')
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Obtener funciones con información completa
     */
    public function getShowtimesWithDetails(): array
    {
        $sql = "SELECT 
                s.*,
                m.title as movie_title,
                m.poster_url as movie_poster,
                m.duration as movie_duration,
                m.rating as movie_rating,
                m.classification as movie_classification,
                r.name as room_name,
                r.capacity as room_capacity,
                r.room_type
                FROM {$this->table} s
                JOIN movies m ON s.movie_id = m.id
                JOIN rooms r ON s.room_id = r.id
                WHERE s.is_active = true
                AND s.show_date >= CURDATE()
                ORDER BY s.show_date ASC, s.show_time ASC";

        return $this->connection->fetchAll($sql);
    }

    /**
     * Obtener funciones por película
     */
    public function getShowtimesByMovie(int $movieId): array
    {
        $sql = "SELECT 
                s.*,
                r.name as room_name,
                r.capacity as room_capacity,
                r.room_type,
                COUNT(DISTINCT b.id) as bookings_count,
                COUNT(DISTINCT bs.seat_id) as occupied_seats
                FROM {$this->table} s
                JOIN rooms r ON s.room_id = r.id
                LEFT JOIN bookings b ON s.id = b.showtime_id 
                    AND b.status IN ('confirmed', 'pending')
                LEFT JOIN booking_seats bs ON b.id = bs.booking_id
                WHERE s.movie_id = ?
                AND s.is_active = true
                AND s.show_date >= CURDATE()
                GROUP BY s.id
                ORDER BY s.show_date ASC, s.show_time ASC";

        return $this->connection->fetchAll($sql, [$movieId]);
    }

    /**
     * Obtener función con detalles completos
     */
    public function getShowtimeWithDetails(int $showtimeId): ?array
    {
        $sql = "SELECT 
                s.*,
                m.title as movie_title,
                m.poster_url as movie_poster,
                m.synopsis as movie_synopsis,
                m.duration as movie_duration,
                m.rating as movie_rating,
                m.classification as movie_classification,
                m.trailer_url as movie_trailer,
                r.name as room_name,
                r.capacity as room_capacity,
                r.rows as room_rows,
                r.columns as room_columns,
                r.room_type,
                COUNT(DISTINCT bs.seat_id) as occupied_seats,
                (r.capacity - COUNT(DISTINCT bs.seat_id)) as available_seats_calc
                FROM {$this->table} s
                JOIN movies m ON s.movie_id = m.id
                JOIN rooms r ON s.room_id = r.id
                LEFT JOIN bookings b ON s.id = b.showtime_id 
                    AND b.status IN ('confirmed', 'pending')
                LEFT JOIN booking_seats bs ON b.id = bs.booking_id
                WHERE s.id = ?
                GROUP BY s.id";

        return $this->connection->fetchOne($sql, [$showtimeId]);
    }

    /**
     * Obtener funciones por sala
     */
    public function getShowtimesByRoom(int $roomId, ?string $date = null): array
    {
        $sql = "SELECT 
                s.*,
                m.title as movie_title,
                m.duration as movie_duration
                FROM {$this->table} s
                JOIN movies m ON s.movie_id = m.id
                WHERE s.room_id = ?
                AND s.is_active = true";

        $params = [$roomId];

        if ($date) {
            $sql .= " AND s.show_date = ?";
            $params[] = $date;
        } else {
            $sql .= " AND s.show_date >= CURDATE()";
        }

        $sql .= " ORDER BY s.show_date ASC, s.show_time ASC";

        return $this->connection->fetchAll($sql, $params);
    }

    /**
     * Verificar si hay conflicto de horario
     */
    public function hasScheduleConflict(int $roomId, string $date, string $time, int $duration, ?int $excludeShowtimeId = null): bool
    {
        // Convertir duración a minutos
        $endTime = date('H:i:s', strtotime($time) + ($duration * 60));

        $sql = "SELECT COUNT(*) as count FROM {$this->table} s
                JOIN movies m ON s.movie_id = m.id
                WHERE s.room_id = ?
                AND s.show_date = ?
                AND s.is_active = true
                AND (
                    (s.show_time <= ? AND ADDTIME(s.show_time, SEC_TO_TIME(m.duration * 60)) > ?)
                    OR (s.show_time < ? AND s.show_time >= ?)
                )";

        $params = [$roomId, $date, $time, $time, $endTime, $time];

        if ($excludeShowtimeId) {
            $sql .= " AND s.id != ?";
            $params[] = $excludeShowtimeId;
        }

        $result = $this->connection->fetchOne($sql, $params);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Obtener funciones por rango de fechas
     */
    public function getShowtimesByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                s.*,
                m.title as movie_title,
                m.poster_url as movie_poster,
                r.name as room_name
                FROM {$this->table} s
                JOIN movies m ON s.movie_id = m.id
                JOIN rooms r ON s.room_id = r.id
                WHERE s.show_date BETWEEN ? AND ?
                AND s.is_active = true
                ORDER BY s.show_date ASC, s.show_time ASC";

        return $this->connection->fetchAll($sql, [$startDate, $endDate]);
    }

    /**
     * Obtener funciones del día
     */
    public function getTodayShowtimes(): array
    {
        return $this->getShowtimesByDateRange(date('Y-m-d'), date('Y-m-d'));
    }

    /**
     * Actualizar asientos disponibles
     */
    public function updateAvailableSeats(int $showtimeId): bool
    {
        $sql = "UPDATE {$this->table} s
                JOIN rooms r ON s.room_id = r.id
                SET s.available_seats = r.capacity - (
                    SELECT COUNT(DISTINCT bs.seat_id)
                    FROM booking_seats bs
                    JOIN bookings b ON bs.booking_id = b.id
                    WHERE b.showtime_id = s.id
                    AND b.status IN ('confirmed', 'pending')
                ),
                s.updated_at = NOW()
                WHERE s.id = ?";

        return $this->connection->getConnection()->exec(
            $this->connection->getConnection()->prepare($sql)->execute([$showtimeId])
        ) !== false;
    }

    /**
     * Activar/desactivar función
     */
    public function toggleActive(int $showtimeId, bool $isActive): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $showtimeId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Verificar si la función ya pasó
     */
    public function isPast(int $showtimeId): bool
    {
        $showtime = $this->find($showtimeId);
        
        if (!$showtime) {
            return true;
        }

        $showtimeDateTime = strtotime($showtime['show_date'] . ' ' . $showtime['show_time']);
        return $showtimeDateTime < time();
    }

    /**
     * Obtener funciones futuras por película
     */
    public function getFutureShowtimesByMovie(int $movieId): array
    {
        $sql = "SELECT 
                s.*,
                r.name as room_name,
                r.room_type
                FROM {$this->table} s
                JOIN rooms r ON s.room_id = r.id
                WHERE s.movie_id = ?
                AND s.is_active = true
                AND CONCAT(s.show_date, ' ', s.show_time) > NOW()
                ORDER BY s.show_date ASC, s.show_time ASC";

        return $this->connection->fetchAll($sql, [$movieId]);
    }

    /**
     * Obtener estadísticas de funciones
     */
    public function getStats(): array
    {
        $total = $this->count();
        
        $active = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->where('show_date', '>=', date('Y-m-d'))
            ->count();

        $today = $this->queryBuilder
            ->table($this->table)
            ->where('show_date', date('Y-m-d'))
            ->where('is_active', true)
            ->count();

        $sql = "SELECT 
                COUNT(*) as total_bookings,
                SUM(b.total_seats) as total_seats_sold,
                SUM(b.final_amount) as total_revenue
                FROM bookings b
                JOIN {$this->table} s ON b.showtime_id = s.id
                WHERE s.show_date >= CURDATE()
                AND b.status = 'confirmed'";

        $bookingStats = $this->connection->fetchOne($sql);

        return [
            'total' => $total,
            'active_future' => $active,
            'today' => $today,
            'total_bookings' => (int)($bookingStats['total_bookings'] ?? 0),
            'total_seats_sold' => (int)($bookingStats['total_seats_sold'] ?? 0),
            'total_revenue' => (float)($bookingStats['total_revenue'] ?? 0)
        ];
    }

    /**
     * Buscar funciones con filtros
     */
    public function searchShowtimes(array $filters): array
    {
        $sql = "SELECT 
                s.*,
                m.title as movie_title,
                m.poster_url as movie_poster,
                m.rating as movie_rating,
                r.name as room_name,
                r.room_type
                FROM {$this->table} s
                JOIN movies m ON s.movie_id = m.id
                JOIN rooms r ON s.room_id = r.id
                WHERE s.is_active = true
                AND s.show_date >= CURDATE()";

        $params = [];

        if (!empty($filters['movie_id'])) {
            $sql .= " AND s.movie_id = ?";
            $params[] = $filters['movie_id'];
        }

        if (!empty($filters['room_id'])) {
            $sql .= " AND s.room_id = ?";
            $params[] = $filters['room_id'];
        }

        if (!empty($filters['date'])) {
            $sql .= " AND s.show_date = ?";
            $params[] = $filters['date'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND s.show_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND s.show_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY s.show_date ASC, s.show_time ASC";

        return $this->connection->fetchAll($sql, $params);
    }
}