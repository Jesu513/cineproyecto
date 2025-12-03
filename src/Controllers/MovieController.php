<?php
// ============================================
// src/Controllers/MovieController.php
// Controlador de películas
// ============================================

namespace App\Controllers;

use App\Services\TMDbService;
use App\Repositories\MovieRepository;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use Exception;

class MovieController
{
    private TMDbService $tmdbService;
    private MovieRepository $movieRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->tmdbService = new TMDbService();
        $this->movieRepository = new MovieRepository();
        $this->logger = new Logger();
    }

    /**
     * GET /api/movies
     * Listar películas con filtros y paginación
     */
    public function index(): void
    {
        try {
            // Obtener parámetros de query
            $page = (int)($_GET['page'] ?? 1);
            $perPage = (int)($_GET['per_page'] ?? 20);
            $perPage = min($perPage, 100); // Máximo 100 por página

            // Filtros
            $filters = [];
            if (!empty($_GET['title'])) {
                $filters['title'] = $_GET['title'];
            }
            if (!empty($_GET['genre'])) {
                $filters['genre'] = $_GET['genre'];
            }
            if (!empty($_GET['min_rating'])) {
                $filters['min_rating'] = (float)$_GET['min_rating'];
            }
            if (!empty($_GET['year'])) {
                $filters['year'] = (int)$_GET['year'];
            }
            if (!empty($_GET['order_by'])) {
                $filters['order_by'] = $_GET['order_by'];
            }
            if (!empty($_GET['order_dir'])) {
                $filters['order_dir'] = strtoupper($_GET['order_dir']);
            }

            // Obtener películas
            $result = $this->movieRepository->getPaginatedWithFilters(
                $page,
                $perPage,
                $filters
            );

            Response::success(
                $result['data'],
                'Películas obtenidas exitosamente',
                200,
                ['pagination' => $result['pagination']]
            );

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener películas');
        }
    }

    /**
     * GET /api/movies/:id
     * Obtener detalle de película
     */
    public function show(int $id): void
    {
        try {
            $movie = $this->movieRepository->find($id);

            if (!$movie) {
                Response::notFound('Película no encontrada');
                return;
            }

            Response::success($movie, 'Película obtenida exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['movie_id' => $id]);
            Response::serverError('Error al obtener película');
        }
    }

    /**
     * POST /api/movies
     * Crear película manualmente (admin)
     */
    public function store(): void
    {
        try {
            // Obtener datos
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar
            $validator = Validator::make($data, [
                'title' => 'required|min:1|max:255',
                'synopsis' => 'required',
                'duration' => 'integer|min:1',
                'release_date' => 'date',
                'rating' => 'numeric|min:0|max:10',
                'language' => 'string|max:10'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Crear película
            $movie = $this->movieRepository->create($validator->validated());

            Response::created($movie, 'Película creada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * PUT /api/movies/:id
     * Actualizar película (admin)
     */
    public function update(int $id): void
    {
        try {
            // Verificar que existe
            if (!$this->movieRepository->exists($id)) {
                Response::notFound('Película no encontrada');
                return;
            }

            // Obtener datos
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar
            $validator = Validator::make($data, [
                'title' => 'min:1|max:255',
                'synopsis' => 'string',
                'duration' => 'integer|min:1',
                'release_date' => 'date',
                'rating' => 'numeric|min:0|max:10',
                'language' => 'string|max:10',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Actualizar película
            $movie = $this->movieRepository->update($id, $validator->validated());

            Response::updated($movie, 'Película actualizada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['movie_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * DELETE /api/movies/:id
     * Eliminar película (admin)
     */
    public function destroy(int $id): void
    {
        try {
            // Verificar que existe
            if (!$this->movieRepository->exists($id)) {
                Response::notFound('Película no encontrada');
                return;
            }

            // Eliminar película
            $this->movieRepository->delete($id);

            Response::deleted('Película eliminada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['movie_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/movies/import/:tmdb_id
     * Importar película desde TMDb (admin)
     */
    public function importFromTMDb(int $tmdbId): void
    {
        try {
            // Verificar si ya existe
            if ($this->movieRepository->existsByTmdbId($tmdbId)) {
                Response::conflict('La película ya existe en el sistema');
                return;
            }

            // Obtener datos de TMDb
            $tmdbData = $this->tmdbService->getMovieDetails($tmdbId);

            // Importar a nuestra BD
            $movie = $this->movieRepository->importFromTMDb($tmdbData);

            Response::created($movie, 'Película importada exitosamente desde TMDb');

        } catch (Exception $e) {
            $this->logger->exception($e, ['tmdb_id' => $tmdbId]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/movies/search/tmdb
     * Buscar películas en TMDb (admin)
     */
    public function searchTMDb(): void
    {
        try {
            $query = $_GET['query'] ?? '';

            if (empty($query)) {
                Response::validationError(['query' => ['El término de búsqueda es requerido']]);
                return;
            }

            $page = (int)($_GET['page'] ?? 1);

            // Buscar en TMDb
            $results = $this->tmdbService->searchMovies($query, $page);

            Response::success($results, 'Búsqueda realizada en TMDb');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/movies/popular
     * Obtener películas populares de TMDb (admin)
     */
    public function getPopularFromTMDb(): void
    {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $results = $this->tmdbService->getPopularMovies($page);

            Response::success($results, 'Películas populares obtenidas de TMDb');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/movies/top-rated
     * Obtener películas mejor calificadas
     */
    public function getTopRated(): void
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $movies = $this->movieRepository->getTopRated($limit);

            Response::success($movies, 'Películas mejor calificadas');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener películas');
        }
    }

    /**
     * GET /api/movies/recent
     * Obtener películas recientes
     */
    public function getRecent(): void
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $movies = $this->movieRepository->getRecentMovies($limit);

            Response::success($movies, 'Películas recientes');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener películas');
        }
    }

    /**
     * GET /api/movies/upcoming
     * Obtener próximos estrenos
     */
    public function getUpcoming(): void
    {
        try {
            $limit = (int)($_GET['limit'] ?? 10);
            $movies = $this->movieRepository->getUpcoming($limit);

            Response::success($movies, 'Próximos estrenos');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener películas');
        }
    }

    /**
     * GET /api/movies/genres
     * Obtener lista de géneros desde TMDb
     */
    public function getGenres(): void
    {
        try {
            $genres = $this->tmdbService->getGenres();
            Response::success($genres, 'Géneros obtenidos exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * PATCH /api/movies/:id/toggle-active
     * Activar/desactivar película (admin)
     */
    public function toggleActive(int $id): void
    {
        try {
            $movie = $this->movieRepository->find($id);

            if (!$movie) {
                Response::notFound('Película no encontrada');
                return;
            }

            $newStatus = !$movie['is_active'];
            $this->movieRepository->toggleActive($id, $newStatus);

            Response::success(
                ['is_active' => $newStatus],
                'Estado de película actualizado'
            );

        } catch (Exception $e) {
            $this->logger->exception($e, ['movie_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/movies/stats
     * Obtener estadísticas de películas (admin)
     */
    public function getStats(): void
    {
        try {
            $stats = $this->movieRepository->getStats();
            Response::success($stats, 'Estadísticas obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener estadísticas');
        }
    }
}