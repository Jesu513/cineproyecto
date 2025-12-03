<?php
// ============================================
// src/Repositories/MovieRepository.php
// Repositorio de películas
// ============================================

namespace App\Repositories;

use App\Models\Movie;

class MovieRepository extends BaseRepository
{
    protected Movie $movieModel;

    public function __construct()
    {
        $this->movieModel = new Movie();
        parent::__construct($this->movieModel);
    }

    /**
     * Buscar por TMDb ID
     */
    public function findByTmdbId(int $tmdbId): ?array
    {
        try {
            return $this->movieModel->findByTmdbId($tmdbId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['tmdb_id' => $tmdbId]);
            throw $e;
        }
    }

    /**
     * Verificar si existe por TMDb ID
     */
    public function existsByTmdbId(int $tmdbId): bool
    {
        try {
            return $this->movieModel->existsByTmdbId($tmdbId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['tmdb_id' => $tmdbId]);
            throw $e;
        }
    }

    /**
     * Obtener películas activas
     */
    public function getActiveMovies(int $limit = 20): array
    {
        try {
            return $this->movieModel->getActiveMovies($limit);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Buscar por título
     */
    public function searchByTitle(string $title): array
    {
        try {
            $this->logger->info('Searching movies by title', ['title' => $title]);
            return $this->movieModel->searchByTitle($title);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['title' => $title]);
            throw $e;
        }
    }

    /**
     * Buscar por género
     */
    public function findByGenre(string $genre): array
    {
        try {
            return $this->movieModel->findByGenre($genre);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['genre' => $genre]);
            throw $e;
        }
    }

    /**
     * Filtrar por rating
     */
    public function filterByRating(float $minRating): array
    {
        try {
            return $this->movieModel->filterByRating($minRating);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['min_rating' => $minRating]);
            throw $e;
        }
    }

    /**
     * Obtener por rango de fechas
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        try {
            return $this->movieModel->getByDateRange($startDate, $endDate);
        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            throw $e;
        }
    }

    /**
     * Obtener películas recientes
     */
    public function getRecentMovies(int $limit = 10): array
    {
        try {
            return $this->movieModel->getRecentMovies($limit);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener mejor calificadas
     */
    public function getTopRated(int $limit = 10): array
    {
        try {
            return $this->movieModel->getTopRated($limit);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener próximos estrenos
     */
    public function getUpcoming(int $limit = 10): array
    {
        try {
            return $this->movieModel->getUpcoming($limit);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Activar/desactivar película
     */
    public function toggleActive(int $movieId, bool $isActive): bool
    {
        try {
            $this->logger->info('Toggle movie active status', [
                'movie_id' => $movieId,
                'is_active' => $isActive
            ]);
            return $this->movieModel->toggleActive($movieId, $isActive);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['movie_id' => $movieId]);
            throw $e;
        }
    }

    /**
     * Actualizar rating
     */
    public function updateRating(int $movieId, float $newRating): bool
    {
        try {
            return $this->movieModel->updateRating($movieId, $newRating);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['movie_id' => $movieId]);
            throw $e;
        }
    }

    /**
     * Obtener estadísticas
     */
    public function getStats(): array
    {
        try {
            return $this->movieModel->getStats();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Búsqueda avanzada
     */
    public function advancedSearch(array $filters): array
    {
        try {
            $this->logger->debug('Advanced movie search', ['filters' => $filters]);
            return $this->movieModel->advancedSearch($filters);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['filters' => $filters]);
            throw $e;
        }
    }

    /**
     * Importar película desde TMDb
     */
    public function importFromTMDb(array $movieData): array
    {
        try {
            // Verificar si ya existe
            if ($this->existsByTmdbId($movieData['tmdb_id'])) {
                throw new \Exception('La película ya existe en el sistema');
            }

            // Crear película
            $movie = $this->create($movieData);

            $this->logger->info('Movie imported from TMDb', [
                'movie_id' => $movie['id'],
                'tmdb_id' => $movieData['tmdb_id'],
                'title' => $movieData['title']
            ]);

            return $movie;

        } catch (\Exception $e) {
            $this->logger->exception($e, ['tmdb_id' => $movieData['tmdb_id'] ?? null]);
            throw $e;
        }
    }

    /**
     * Actualizar película desde TMDb
     */
    public function updateFromTMDb(int $movieId, array $movieData): ?array
    {
        try {
            $movie = $this->update($movieId, $movieData);

            $this->logger->info('Movie updated from TMDb', [
                'movie_id' => $movieId,
                'title' => $movieData['title'] ?? null
            ]);

            return $movie;

        } catch (\Exception $e) {
            $this->logger->exception($e, ['movie_id' => $movieId]);
            throw $e;
        }
    }

    /**
     * Obtener películas con paginación y filtros
     */
    public function getPaginatedWithFilters(
        int $page = 1,
        int $perPage = 20,
        array $filters = []
    ): array {
        try {
            // Si hay filtros, usar búsqueda avanzada
            if (!empty($filters)) {
                $results = $this->advancedSearch($filters);
                
                // Aplicar paginación manualmente
                $total = count($results);
                $offset = ($page - 1) * $perPage;
                $paginatedResults = array_slice($results, $offset, $perPage);

                return [
                    'data' => $paginatedResults,
                    'pagination' => [
                        'total' => $total,
                        'count' => count($paginatedResults),
                        'per_page' => $perPage,
                        'current_page' => $page,
                        'total_pages' => ceil($total / $perPage)
                    ]
                ];
            }

            // Sin filtros, usar paginación normal
            return $this->paginate($page, $perPage);

        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }
}