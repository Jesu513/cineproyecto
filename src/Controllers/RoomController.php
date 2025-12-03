<?php
// ============================================
// src/Controllers/RoomController.php
// Controlador de salas
// ============================================

namespace App\Controllers;

use App\Repositories\RoomRepository;
use App\Repositories\SeatRepository;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use Exception;

class RoomController
{
    private RoomRepository $roomRepository;
    private SeatRepository $seatRepository;
    private Logger $logger;

    public function __construct()
    {
        $this->roomRepository = new RoomRepository();
        $this->seatRepository = new SeatRepository();
        $this->logger = new Logger();
    }

    /**
     * GET /api/rooms
     * Listar todas las salas
     */
    public function index(): void
    {
        try {
            $rooms = $this->roomRepository->getAllRoomsWithSeatsCount();

            Response::success($rooms, 'Salas obtenidas exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener salas');
        }
    }

    /**
     * GET /api/rooms/:id
     * Obtener detalle de sala
     */
    public function show(int $id): void
    {
        try {
            $room = $this->roomRepository->getRoomWithSeatsCount($id);

            if (!$room) {
                Response::notFound('Sala no encontrada');
                return;
            }

            Response::success($room, 'Sala obtenida exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['room_id' => $id]);
            Response::serverError('Error al obtener sala');
        }
    }

    /**
     * POST /api/rooms
     * Crear sala con asientos (admin)
     */
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar datos de sala
            $validator = Validator::make($data, [
                'name' => 'required|min:1|max:50',
                'rows' => 'required|integer|min:1|max:26',
                'columns' => 'required|integer|min:1|max:30',
                'room_type' => 'in:standard,vip,imax,3d,4dx'
            ], [
                'name.required' => 'El nombre es requerido',
                'rows.required' => 'El número de filas es requerido',
                'rows.max' => 'Máximo 26 filas (A-Z)',
                'columns.required' => 'El número de columnas es requerido',
                'columns.max' => 'Máximo 30 asientos por fila'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Verificar que no exista sala con mismo nombre
            if ($this->roomRepository->findByName($data['name'])) {
                Response::conflict('Ya existe una sala con ese nombre');
                return;
            }

            // Calcular capacidad
            $capacity = $data['rows'] * $data['columns'];

            $roomData = [
                'name' => $data['name'],
                'capacity' => $capacity,
                'rows' => $data['rows'],
                'columns' => $data['columns'],
                'room_type' => $data['room_type'] ?? 'standard',
                'is_active' => true
            ];

            // Opciones de asientos
            $seatOptions = [];
            if (!empty($data['vip_rows'])) {
                $seatOptions['vip_rows'] = $data['vip_rows'];
            }

            // Crear sala con asientos
            $result = $this->roomRepository->createRoomWithSeats($roomData, $seatOptions);

            Response::created($result, 'Sala creada exitosamente con ' . $result['seats_created'] . ' asientos');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * PUT /api/rooms/:id
     * Actualizar sala (admin)
     */
    public function update(int $id): void
    {
        try {
            if (!$this->roomRepository->exists($id)) {
                Response::notFound('Sala no encontrada');
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validar
            $validator = Validator::make($data, [
                'name' => 'min:1|max:50',
                'rows' => 'integer|min:1|max:26',
                'columns' => 'integer|min:1|max:30',
                'room_type' => 'in:standard,vip,imax,3d,4dx',
                'is_active' => 'boolean',
                'regenerate_seats' => 'boolean'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            $regenerateSeats = $data['regenerate_seats'] ?? false;
            unset($data['regenerate_seats']);

            // Recalcular capacidad si cambian filas o columnas
            if (isset($data['rows']) && isset($data['columns'])) {
                $data['capacity'] = $data['rows'] * $data['columns'];
            }

            // Actualizar sala
            $room = $this->roomRepository->updateRoomWithSeats($id, $data, $regenerateSeats);

            Response::updated($room, 'Sala actualizada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['room_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * DELETE /api/rooms/:id
     * Eliminar sala (admin)
     */
    public function destroy(int $id): void
    {
        try {
            if (!$this->roomRepository->exists($id)) {
                Response::notFound('Sala no encontrada');
                return;
            }

            // Verificar si tiene funciones activas
            if ($this->roomRepository->hasActiveShowtimes($id)) {
                Response::conflict('No se puede eliminar una sala con funciones activas');
                return;
            }

            // Eliminar sala con asientos
            $this->roomRepository->deleteRoomWithSeats($id);

            Response::deleted('Sala eliminada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['room_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/rooms/:id/seats
     * Obtener mapa de asientos de la sala
     */
    public function getSeats(int $id): void
    {
        try {
            if (!$this->roomRepository->exists($id)) {
                Response::notFound('Sala no encontrada');
                return;
            }

            $seats = $this->seatRepository->getSeatsByRoom($id);
            $seatsByType = $this->seatRepository->countSeatsByType($id);

            Response::success([
                'seats' => $seats,
                'summary' => $seatsByType
            ], 'Asientos obtenidos exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e, ['room_id' => $id]);
            Response::serverError('Error al obtener asientos');
        }
    }

    /**
     * PATCH /api/rooms/:id/toggle-active
     * Activar/desactivar sala (admin)
     */
    public function toggleActive(int $id): void
    {
        try {
            $room = $this->roomRepository->find($id);

            if (!$room) {
                Response::notFound('Sala no encontrada');
                return;
            }

            $newStatus = !$room['is_active'];
            $this->roomRepository->toggleActive($id, $newStatus);

            Response::success(
                ['is_active' => $newStatus],
                'Estado de sala actualizado'
            );

        } catch (Exception $e) {
            $this->logger->exception($e, ['room_id' => $id]);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/rooms/stats
     * Obtener estadísticas de salas (admin)
     */
    public function getStats(): void
    {
        try {
            $stats = $this->roomRepository->getStats();
            Response::success($stats, 'Estadísticas obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener estadísticas');
        }
    }

    /**
     * GET /api/rooms/active
     * Obtener salas activas
     */
    public function getActive(): void
    {
        try {
            $rooms = $this->roomRepository->getActiveRooms();
            Response::success($rooms, 'Salas activas obtenidas');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::serverError('Error al obtener salas activas');
        }
    }

    /**
     * GET /api/rooms/:id/availability
     * Verificar disponibilidad de sala en fecha/hora
     */
    public function checkAvailability(int $id): void
    {
        try {
            if (!$this->roomRepository->exists($id)) {
                Response::notFound('Sala no encontrada');
                return;
            }

            $date = $_GET['date'] ?? '';
            $time = $_GET['time'] ?? '';

            if (empty($date) || empty($time)) {
                Response::validationError([
                    'date' => ['La fecha es requerida'],
                    'time' => ['La hora es requerida']
                ]);
                return;
            }

            $isAvailable = $this->roomRepository->isAvailable($id, $date, $time);

            Response::success([
                'available' => $isAvailable,
                'date' => $date,
                'time' => $time
            ], $isAvailable ? 'Sala disponible' : 'Sala no disponible');

        } catch (Exception $e) {
            $this->logger->exception($e, ['room_id' => $id]);
            Response::serverError('Error al verificar disponibilidad');
        }
    }
}