<?php
// ============================================
// src/Controllers/ShowtimeController.php
// Controlador de horarios
// ============================================

namespace App\Controllers;

use App\Repositories\ShowtimeRepository;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use Exception;

class ShowtimeController
{
    private ShowtimeRepository $showtimeRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->showtimeRepository = new ShowtimeRepository();
        $this->logger = new Logger();
    }

    /**
     * GET /api/showtimes
     * Listar funciones disponibles con filtros
     */
    public function index(): void
    {
        try {
            // Obtener filtros
            $filters = [];
            
            if (!empty($_GET['movie_id'])) {
                $filters['movie_id'] = (int)$_GET['movie_id'];
            }
            if (!empty($_GET['room_id'])) {
                $filters['room_id'] = (int)$_GET['room_id'];
            }
            if (!empty($_GET['date'])) {
                $filters['date'] = $_GET['date'];
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }

            // Buscar con filtros o obtener todas
            if (!empty($filters)) {
                $showtimes = $this->showtimeRepository->searchShowtimes($filters);
            } else {
                $showtimes = $this->showtimeRepository->getShowtimesWithDetails();
            }

            Response::success($showtimes, 'Funciones obtenidas exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener funciones');
        }
    }

    /**
     * GET /api/showtimes/:id
     * Obtener detalle de función
     */
    public function show(int $id): void
    {
        try {
            $showtime = $this->showtimeRepository->getShowtimeWithDetails($id);

            if (!$showtime) {
                Response::notFound('Función no encontrada');
                return;
            }

            Response::success($showtime, 'Función obtenida exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $id]);
            Response::serverError('Error al obtener función');
        }
    }

    /**
     * GET /api/showtimes/movie/:movie_id
     * Obtener funciones de una película
     */
    public function getByMovie(int $movieId): void
    {
        try {
            $showtimes = $this->showtimeRepository->getShowtimesByMovie($movieId);

            Response::success($showtimes, 'Funciones de la película obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e, ['movie_id' => $movieId]);
            Response::serverError('Error al obtener funciones');
        }
    }

    /**
     * GET /api/showtimes/room/:room_id
     * Obtener funciones de una sala
     */
    public function getByRoom(int $roomId): void
    {
        try {
            $date = $_GET['date'] ?? null;
            $showtimes = $this->showtimeRepository->getShowtimesByRoom($roomId, $date);

            Response::success($showtimes, 'Funciones de la sala obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e, ['room_id' => $roomId]);
            Response::serverError('Error al obtener funciones');
        }
    }

    /**
     * GET /api/showtimes/today
     * Obtener funciones del día
     */
    public function getToday(): void
    {
        try {
            $showtimes = $this->showtimeRepository->getTodayShowtimes();

            Response::success($showtimes, 'Funciones del día obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener funciones');
        }
    }

    /**
     * POST /api/showtimes
     * Crear función (admin)
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar
            $validator = Validator::make($data, [
                'movie_id' => 'required|integer|exists:movies,id',
                'room_id' => 'required|integer|exists:rooms,id',
                'show_date' => 'required|date',
                'show_time' => 'required',
                'base_price' => 'required|numeric|min:0'
            ], [
                'movie_id.required' => 'La película es requerida',
                'movie_id.exists' => 'La película no existe',
                'room_id.required' => 'La sala es requerida',
                'room_id.exists' => 'La sala no existe',
                'show_date.required' => 'La fecha es requerida',
                'show_time.required' => 'La hora es requerida',
                'base_price.required' => 'El precio base es requerido'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Verificar que la fecha no sea pasada
            $showDateTime = strtotime($data['show_date'] . ' ' . $data['show_time']);
            if ($showDateTime < time()) {
                Response::validationError([
                    'show_date' => ['No se puede crear una función en el pasado']
                ]);
                return;
            }

            // Crear función
            $showtime = $this->showtimeRepository->createShowtime($validator->validated());

            Response::created($showtime, 'Función creada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * PUT /api/showtimes/:id
     * Actualizar función (admin)
     */
    public function update(int $id): void
    {
        try {
            if (!$this->showtimeRepository->exists($id)) {
                Response::notFound('Función no encontrada');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validar
            $validator = Validator::make($data, [
                'movie_id' => 'integer|exists:movies,id',
                'room_id' => 'integer|exists:rooms,id',
                'show_date' => 'date',
                'show_time' => 'string',
                'base_price' => 'numeric|min:0',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Actualizar función
            $showtime = $this->showtimeRepository->updateShowtime($id, $validator->validated());

            Response::updated($showtime, 'Función actualizada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * DELETE /api/showtimes/:id
     * Eliminar función (admin)
     */
    public function destroy(int $id): void
    {
        try {
            if (!$this->showtimeRepository->exists($id)) {
                Response::notFound('Función no encontrada');
                return;
            }

            // Eliminar función
            $this->showtimeRepository->deleteShowtime($id);

            Response::deleted('Función eliminada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * PATCH /api/showtimes/:id/toggle-active
     * Activar/desactivar función (admin)
     */
    public function toggleActive(int $id): void
    {
        try {
            $showtime = $this->showtimeRepository->find($id);

            if (!$showtime) {
                Response::notFound('Función no encontrada');
                return;
            }

            $newStatus = !$showtime['is_active'];
            $this->showtimeRepository->toggleActive($id, $newStatus);

            Response::success(
                ['is_active' => $newStatus],
                'Estado de función actualizado'
            );

        } catch (Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/showtimes/stats
     * Obtener estadísticas (admin)
     */
    public function getStats(): void
    {
        try {
            $stats = $this->showtimeRepository->getStats();
            Response::success($stats, 'Estadísticas obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener estadísticas');
        }
    }

    /**
     * POST /api/showtimes/bulk
     * Crear múltiples funciones (admin)
     */
    public function bulkCreate(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar estructura
            if (!isset($data['showtimes']) || !is_array($data['showtimes'])) {
                Response::validationError([
                    'showtimes' => ['Se requiere un array de funciones']
                ]);
                return;
            }

            $created = [];
            $errors = [];

            foreach ($data['showtimes'] as $index => $showtimeData) {
                try {
                    $showtime = $this->showtimeRepository->createShowtime($showtimeData);
                    $created[] = $showtime;
                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'data' => $showtimeData,
                        'error' => $e->getMessage()
                    ];
                }
            }

            Response::success([
                'created' => $created,
                'errors' => $errors,
                'success_count' => count($created),
                'error_count' => count($errors)
            ], count($created) . ' funciones creadas exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/showtimes/date-range
     * Obtener funciones por rango de fechas
     */
    public function getByDateRange(): void
    {
        try {
            $startDate = $_GET['start_date'] ?? date('Y-m-d');
            $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+7 days'));

            $showtimes = $this->showtimeRepository->getShowtimesByDateRange($startDate, $endDate);

            Response::success($showtimes, 'Funciones obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener funciones');
        }
    }
}