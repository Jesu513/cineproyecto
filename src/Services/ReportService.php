<?php

namespace App\Services;

use App\Database\Connection;

class ReportService
{
    protected Connection $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Datos generales para el dashboard admin
     */
    public function getDashboardSummary(): array
    {
        $today = date('Y-m-d');

        $totalIngresos = $this->db->fetchOne("
            SELECT COALESCE(SUM(final_amount), 0) as total
            FROM bookings
            WHERE status IN ('confirmed', 'completed')
        ");

        $ingresosHoy = $this->db->fetchOne("
            SELECT COALESCE(SUM(final_amount), 0) as total
            FROM bookings
            WHERE status IN ('confirmed', 'completed')
            AND DATE(created_at) = ?
        ", [$today]);

        $totalReservas = $this->db->fetchOne("
            SELECT COUNT(*) AS total FROM bookings
        ");

        $totalUsuarios = $this->db->fetchOne("
            SELECT COUNT(*) AS total FROM users
        ");

        $topPeliculas = $this->db->fetchAll("
            SELECT movie_title, COUNT(*) AS total_reservas
            FROM bookings_complete
            GROUP BY movie_title
            ORDER BY total_reservas DESC
            LIMIT 5
        ");

        return [
            'totals' => [
                'ingresos' => (float)$totalIngresos['total'],
                'ingresos_hoy' => (float)$ingresosHoy['total'],
                'reservas' => (int)$totalReservas['total'],
                'usuarios' => (int)$totalUsuarios['total'],
            ],
            'top_movies' => $topPeliculas
        ];
    }

    /**
     * Reporte de ocupación de funciones
     */
    public function getOccupancyReport(?string $from = null, ?string $to = null): array
    {
        $sql = "SELECT * FROM showtime_occupancy WHERE 1=1";
        $params = [];

        if ($from) {
            $sql .= " AND show_date >= ?";
            $params[] = $from;
        }

        if ($to) {
            $sql .= " AND show_date <= ?";
            $params[] = $to;
        }

        $sql .= " ORDER BY show_date DESC, show_time DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Reporte de ingresos por rango de fechas
     */
    public function getRevenueReport(?string $from = null, ?string $to = null): array
    {
        $sql = "
            SELECT 
                DATE(created_at) as fecha,
                COUNT(*) as total_reservas,
                SUM(final_amount) as total_ingresos
            FROM bookings
            WHERE status IN ('confirmed', 'completed')
        ";
        $params = [];

        if ($from) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $from;
        }

        if ($to) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $to;
        }

        $sql .= " GROUP BY DATE(created_at)
                  ORDER BY fecha DESC";

        return $this->db->fetchAll($sql, $params);
    }
}
