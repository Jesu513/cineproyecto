<?php
// ============================================
// src/Models/Movie.php
// Modelo de película
// ============================================

namespace App\Models;

class Movie extends BaseModel
{
    protected string $table = 'movies';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'tmdb_id',
        'title',
        'original_title',
        'synopsis',
        'duration',
        'release_date',
        'poster_url',
        'backdrop_url',
        'trailer_url',
        'rating',
        'language',
        'genres',
        'cast',
        'director',
        'classification',
        'is_active'
    ];

    protected array $casts = [
        'id' => 'int',
        'tmdb_id' => 'int',
        'duration' => 'int',
        'rating' => 'float',
        'is_active' => 'bool',
        'genres' => 'json',
        'cast' => 'json',
        'release_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Buscar por TMDb ID
     */
    public function findByTmdbId(int $tmdbId): ?array
    {
        return $this->findBy('tmdb_id', $tmdbId);
    }

    /**
     * Verificar si existe por TMDb ID
     */
    public function existsByTmdbId(int $tmdbId): bool
    {
        return $this->queryBuilder
            ->table($this->table)
            ->where('tmdb_id', $tmdbId)
            ->exists();
    }

    /**
     * Buscar películas activas
     */
    public function getActiveMovies(int $limit = 20): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Buscar por título (búsqueda parcial)
     */
    public function searchByTitle(string $title): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE title LIKE ? OR original_title LIKE ?
                ORDER BY rating DESC";
        
        $searchTerm = "%{$title}%";
        $results = $this->connection->fetchAll($sql, [$searchTerm, $searchTerm]);

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Buscar por género
     */
    public function findByGenre(string $genre): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE JSON_CONTAINS(genres, ?)
                AND is_active = true
                ORDER BY rating DESC";

        $results = $this->connection->fetchAll($sql, [json_encode($genre)]);

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Filtrar por rating mínimo
     */
    public function filterByRating(float $minRating): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('rating', '>=', $minRating)
            ->where('is_active', true)
            ->orderBy('rating', 'DESC')
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Obtener películas por rango de fechas
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE release_date BETWEEN ? AND ?
                AND is_active = true
                ORDER BY release_date DESC";

        $results = $this->connection->fetchAll($sql, [$startDate, $endDate]);

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Obtener películas recientes
     */
    public function getRecentMovies(int $limit = 10): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->orderBy('release_date', 'DESC')
            ->limit($limit)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Obtener películas mejor calificadas
     */
    public function getTopRated(int $limit = 10): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->where('rating', '>=', $this->config['filters']['min_vote_average'] ?? 7.0)
            ->orderBy('rating', 'DESC')
            ->limit($limit)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Obtener próximos estrenos
     */
    public function getUpcoming(int $limit = 10): array
    {
        $today = date('Y-m-d');
        
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('release_date', '>', $today)
            ->where('is_active', true)
            ->orderBy('release_date', 'ASC')
            ->limit($limit)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Activar/desactivar película
     */
    public function toggleActive(int $movieId, bool $isActive): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $movieId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Actualizar rating
     */
    public function updateRating(int $movieId, float $newRating): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $movieId)
            ->update([
                'rating' => $newRating,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Obtener estadísticas de películas
     */
    public function getStats(): array
    {
        $total = $this->count();
        
        $active = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->count();

        $avgRating = $this->connection->fetchColumn(
            "SELECT AVG(rating) FROM {$this->table} WHERE is_active = true"
        );

        $byYear = $this->connection->fetchAll(
            "SELECT YEAR(release_date) as year, COUNT(*) as count 
             FROM {$this->table} 
             WHERE release_date IS NOT NULL
             GROUP BY YEAR(release_date)
             ORDER BY year DESC
             LIMIT 5"
        );

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'average_rating' => round($avgRating, 2),
            'by_year' => $byYear
        ];
    }

    /**
     * Búsqueda avanzada con múltiples filtros
     */
    public function advancedSearch(array $filters): array
    {
        $query = $this->queryBuilder->table($this->table);

        // Filtro por título
        if (!empty($filters['title'])) {
            $query->where('title', 'LIKE', "%{$filters['title']}%");
        }

        // Filtro por género
        if (!empty($filters['genre'])) {
            // Esto requiere una query SQL raw
            $sql = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];

            if (!empty($filters['genre'])) {
                $sql .= " AND JSON_CONTAINS(genres, ?)";
                $params[] = json_encode($filters['genre']);
            }

            if (!empty($filters['min_rating'])) {
                $sql .= " AND rating >= ?";
                $params[] = $filters['min_rating'];
            }

            if (!empty($filters['year'])) {
                $sql .= " AND YEAR(release_date) = ?";
                $params[] = $filters['year'];
            }

            $sql .= " AND is_active = true ORDER BY rating DESC";

            $results = $this->connection->fetchAll($sql, $params);
            return array_map(fn($item) => $this->hideAttributes($item), $results);
        }

        // Filtro por rating mínimo
        if (!empty($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        // Filtro por año
        if (!empty($filters['year'])) {
            $query->where('YEAR(release_date)', '=', $filters['year']);
        }

        // Solo activas
        $query->where('is_active', true);

        // Ordenamiento
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $query->orderBy($orderBy, $orderDir);

        $results = $query->get();
        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }
}