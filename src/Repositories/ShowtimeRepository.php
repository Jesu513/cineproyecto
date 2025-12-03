<?php
// ============================================
// src/Repositories/ShowtimeRepository.php
// Repositorio de horarios
// ============================================

namespace App\Repositories;

use App\Models\Showtime;
use App\Models\Room;
use App\Models\Movie;

class ShowtimeRepository extends BaseRepository
{
    protected Showtime $showtimeModel;
    protected Room $roomModel;
    protected Movie $movieModel;

    public function __construct()
    {
        $this->showtimeModel = new Showtime();
        $this->roomModel = new Room();
        $this->movieModel = new Movie();
        parent::__construct($this->showtimeModel);
    }

    /**
     * Crear función con validaciones
     */
    public function createShowtime(array $data): array
    {
        try {
            // Verificar que la película existe y está activa
            $movie = $this->movieModel->find($data['movie_id']);
            if (!$movie || !$movie['is_active']) {
                throw new \Exception('Película no encontrada o inactiva');
            }

            // Verificar que la sala existe y está activa
            $room = $this->roomModel->find($data['room_id']);
            if (!$room || !$room['is_active']) {
                throw new \Exception('Sala no encontrada o inactiva');
            }

            // Verificar conflictos de horario
            if ($this->showtimeModel->hasScheduleConflict(
                $data['room_id'],
                $data['show_date'],
                $data['show_time'],
                $movie['duration']
            )) {
                throw new \Exception('Conflicto de horario. La sala ya tiene una función en ese horario.');
            }

            // Establecer asientos disponibles igual a la capacidad de la sala
            $data['available_seats'] = $room['capacity'];

            // Crear función
            $showtime = $this->create($data);

            $this->logger->info('Showtime created', [
                'showtime_id' => $showtime['id'],
                'movie_id' => $data['movie_id'],
                'room_id' => $data['room_id'],
                'date' => $data['show_date'],
                'time' => $data['show_time']
            ]);

            return $showtime;

        } catch (\Exception $e) {
            $this->logger->exception($e, ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Actualizar función con validaciones
     */
    public function updateShowtime(int $id, array $data): ?array
    {
        try {
            $showtime = $this->find($id);
            
            if (!$showtime) {
                throw new \Exception('Función no encontrada');
            }

            // Verificar si la función ya pasó
            if ($this->showtimeModel->isPast($id)) {
                throw new \Exception('No se puede modificar una función que ya pasó');
            }

            // Si se cambia la película, verificar que existe
            if (isset($data['movie_id'])) {
                $movie = $this->movieModel->find($data['movie_id']);
                if (!$movie || !$movie['is_active']) {
                    throw new \Exception('Película no encontrada o inactiva');
                }
            }

            // Si se cambia la sala, verificar que existe
            if (isset($data['room_id'])) {
                $room = $this->roomModel->find($data['room_id']);
                if (!$room || !$room['is_active']) {
                    throw new \Exception('Sala no encontrada o inactiva');
                }
            }

            // Verificar conflictos si se cambian fecha, hora o sala
            if (isset($data['show_date']) || isset($data['show_time']) || isset($data['room_id'])) {
                $movieId = $data['movie_id'] ?? $showtime['movie_id'];
                $movie = $this->movieModel->find($movieId);

                $hasConflict = $this->showtimeModel->hasScheduleConflict(
                    $data['room_id'] ?? $showtime['room_id'],
                    $data['show_date'] ?? $showtime['show_date'],
                    $data['show_time'] ?? $showtime['show_time'],
                    $movie['duration'],
                    $id
                );

                if ($hasConflict) {
                    throw new \Exception('Conflicto de horario. La sala ya tiene una función en ese horario.');
                }
            }

            // Actualizar función
            $updated = $this->update($id, $data);

            $this->logger->info('Showtime updated', [
                'showtime_id' => $id,
                'changes' => array_keys($data)
            ]);

            return $updated;

        } catch (\Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $id, 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Obtener funciones activas
     */
    public function getActiveShowtimes(): array
    {
        try {
            return $this->showtimeModel->getActiveShowtimes();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener funciones con detalles
     */
    public function getShowtimesWithDetails(): array
    {
        try {
            return $this->showtimeModel->getShowtimesWithDetails();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener funciones por película
     */
    public function getShowtimesByMovie(int $movieId): array
    {
        try {
            return $this->showtimeModel->getShowtimesByMovie($movieId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['movie_id' => $movieId]);
            throw $e;
        }
    }

    /**
     * Obtener función con detalles completos
     */
    public function getShowtimeWithDetails(int $showtimeId): ?array
    {
        try {
            return $this->showtimeModel->getShowtimeWithDetails($showtimeId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $showtimeId]);
            throw $e;
        }
    }

    /**
     * Obtener funciones por sala
     */
    public function getShowtimesByRoom(int $roomId, ?string $date = null): array
    {
        try {
            return $this->showtimeModel->getShowtimesByRoom($roomId, $date);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['room_id' => $roomId, 'date' => $date]);
            throw $e;
        }
    }

    /**
     * Obtener funciones por rango de fechas
     */
    public function getShowtimesByDateRange(string $startDate, string $endDate): array
    {
        try {
            return $this->showtimeModel->getShowtimesByDateRange($startDate, $endDate);
        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            throw $e;
        }
    }

    /**
     * Obtener funciones del día
     */
    public function getTodayShowtimes(): array
    {
        try {
            return $this->showtimeModel->getTodayShowtimes();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Actualizar asientos disponibles
     */
    public function updateAvailableSeats(int $showtimeId): bool
    {
        try {
            return $this->showtimeModel->updateAvailableSeats($showtimeId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $showtimeId]);
            throw $e;
        }
    }

    /**
     * Activar/desactivar función
     */
    public function toggleActive(int $showtimeId, bool $isActive): bool
    {
        try {
            $this->logger->info('Toggle showtime active status', [
                'showtime_id' => $showtimeId,
                'is_active' => $isActive
            ]);
            return $this->showtimeModel->toggleActive($showtimeId, $isActive);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $showtimeId]);
            throw $e;
        }
    }

    /**
     * Verificar si la función ya pasó
     */
    public function isPast(int $showtimeId): bool
    {
        try {
            return $this->showtimeModel->isPast($showtimeId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $showtimeId]);
            throw $e;
        }
    }

    /**
     * Obtener funciones futuras por película
     */
    public function getFutureShowtimesByMovie(int $movieId): array
    {
        try {
            return $this->showtimeModel->getFutureShowtimesByMovie($movieId);
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
            return $this->showtimeModel->getStats();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Buscar funciones con filtros
     */
    public function searchShowtimes(array $filters): array
    {
        try {
            $this->logger->debug('Searching showtimes', ['filters' => $filters]);
            return $this->showtimeModel->searchShowtimes($filters);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['filters' => $filters]);
            throw $e;
        }
    }

    /**
     * Eliminar función
     */
    public function deleteShowtime(int $id): bool
    {
        try {
            // Verificar si tiene reservas
            $sql = "SELECT COUNT(*) as count FROM bookings 
                    WHERE showtime_id = ? 
                    AND status IN ('confirmed', 'pending')";
            
            $result = $this->showtimeModel->connection->fetchOne($sql, [$id]);
            
            if (($result['count'] ?? 0) > 0) {
                throw new \Exception('No se puede eliminar una función con reservas activas');
            }

            $deleted = $this->delete($id);

            $this->logger->info('Showtime deleted', ['showtime_id' => $id]);

            return $deleted;

        } catch (\Exception $e) {
            $this->logger->exception($e, ['showtime_id' => $id]);
            throw $e;
        }
    }
}