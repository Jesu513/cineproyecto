<?php
// ============================================
// src/Models/Room.php
// Modelo de sala de cine
// ============================================

namespace App\Models;

class Room extends BaseModel
{
    protected string $table = 'rooms';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'capacity',
        'rows',
        'columns',
        'room_type',
        'is_active'
    ];

    protected array $casts = [
        'id' => 'int',
        'capacity' => 'int',
        'rows' => 'int',
        'columns' => 'int',
        'is_active' => 'bool',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Obtener salas activas
     */
    public function getActiveRooms(): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->orderBy('name', 'ASC')
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Obtener sala con conteo de asientos
     */
    public function getRoomWithSeatsCount(int $roomId): ?array
    {
        $sql = "SELECT r.*, 
                COUNT(s.id) as total_seats,
                SUM(CASE WHEN s.seat_type = 'vip' THEN 1 ELSE 0 END) as vip_seats,
                SUM(CASE WHEN s.seat_type = 'standard' THEN 1 ELSE 0 END) as standard_seats,
                SUM(CASE WHEN s.seat_type = 'disabled' THEN 1 ELSE 0 END) as disabled_seats
                FROM {$this->table} r
                LEFT JOIN seats s ON r.id = s.room_id
                WHERE r.id = ?
                GROUP BY r.id";

        return $this->connection->fetchOne($sql, [$roomId]);
    }

    /**
     * Obtener todas las salas con conteo de asientos
     */
    public function getAllRoomsWithSeatsCount(): array
    {
        $sql = "SELECT r.*, 
                COUNT(s.id) as total_seats,
                SUM(CASE WHEN s.seat_type = 'vip' THEN 1 ELSE 0 END) as vip_seats,
                SUM(CASE WHEN s.seat_type = 'standard' THEN 1 ELSE 0 END) as standard_seats
                FROM {$this->table} r
                LEFT JOIN seats s ON r.id = s.room_id
                GROUP BY r.id
                ORDER BY r.name ASC";

        return $this->connection->fetchAll($sql);
    }

    /**
     * Verificar si sala tiene funciones activas
     */
    public function hasActiveShowtimes(int $roomId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM showtimes 
                WHERE room_id = ? 
                AND show_date >= CURDATE() 
                AND is_active = true";

        $result = $this->connection->fetchOne($sql, [$roomId]);
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Activar/desactivar sala
     */
    public function toggleActive(int $roomId, bool $isActive): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $roomId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Obtener estadísticas de salas
     */
    public function getStats(): array
    {
        $total = $this->count();
        
        $active = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->count();

        $totalCapacity = $this->connection->fetchColumn(
            "SELECT SUM(capacity) FROM {$this->table} WHERE is_active = true"
        );

        $byType = $this->connection->fetchAll(
            "SELECT room_type, COUNT(*) as count 
             FROM {$this->table} 
             GROUP BY room_type"
        );

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'total_capacity' => (int)$totalCapacity,
            'by_type' => $byType
        ];
    }

    /**
     * Buscar por nombre
     */
    public function findByName(string $name): ?array
    {
        return $this->findBy('name', $name);
    }

    /**
     * Verificar disponibilidad de sala en horario
     */
    public function isAvailable(int $roomId, string $date, string $time): bool
    {
        $sql = "SELECT COUNT(*) as count FROM showtimes 
                WHERE room_id = ? 
                AND show_date = ? 
                AND show_time = ?
                AND is_active = true";

        $result = $this->connection->fetchOne($sql, [$roomId, $date, $time]);
        return ($result['count'] ?? 0) === 0;
    }
}