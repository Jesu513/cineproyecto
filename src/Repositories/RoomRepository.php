<?php
// ============================================
// src/Repositories/RoomRepository.php
// Repositorio de salas
// ============================================

namespace App\Repositories;

use App\Models\Room;
use App\Models\Seat;

class RoomRepository extends BaseRepository
{
    protected Room $roomModel;
    protected Seat $seatModel;

    public function __construct()
    {
        $this->roomModel = new Room();
        $this->seatModel = new Seat();
        parent::__construct($this->roomModel);
    }

    /**
     * Crear sala con asientos
     */
    public function createRoomWithSeats(array $roomData, array $seatOptions = []): array
    {
        try {
            $this->beginTransaction();

            // Crear sala
            $room = $this->create($roomData);

            // Crear asientos automáticamente
            $seatsCreated = $this->seatModel->createSeatsForRoom(
                $room['id'],
                $roomData['rows'],
                $roomData['columns'],
                $seatOptions
            );

            $this->commit();

            $this->logger->info('Room created with seats', [
                'room_id' => $room['id'],
                'seats_created' => $seatsCreated
            ]);

            return [
                'room' => $room,
                'seats_created' => $seatsCreated
            ];

        } catch (\Exception $e) {
            $this->rollBack();
            $this->logger->exception($e, ['room_data' => $roomData]);
            throw $e;
        }
    }

    /**
     * Obtener salas activas
     */
    public function getActiveRooms(): array
    {
        try {
            return $this->roomModel->getActiveRooms();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener sala con conteo de asientos
     */
    public function getRoomWithSeatsCount(int $roomId): ?array
    {
        try {
            return $this->roomModel->getRoomWithSeatsCount($roomId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['room_id' => $roomId]);
            throw $e;
        }
    }

    /**
     * Obtener todas las salas con conteo
     */
    public function getAllRoomsWithSeatsCount(): array
    {
        try {
            return $this->roomModel->getAllRoomsWithSeatsCount();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Verificar si tiene funciones activas
     */
    public function hasActiveShowtimes(int $roomId): bool
    {
        try {
            return $this->roomModel->hasActiveShowtimes($roomId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['room_id' => $roomId]);
            throw $e;
        }
    }

    /**
     * Activar/desactivar sala
     */
    public function toggleActive(int $roomId, bool $isActive): bool
    {
        try {
            $this->logger->info('Toggle room active status', [
                'room_id' => $roomId,
                'is_active' => $isActive
            ]);
            return $this->roomModel->toggleActive($roomId, $isActive);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['room_id' => $roomId]);
            throw $e;
        }
    }

    /**
     * Obtener estadísticas
     */
    public function getStats(): array
    {
        try {
            return $this->roomModel->getStats();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Eliminar sala con asientos
     */
    public function deleteRoomWithSeats(int $roomId): bool
    {
        try {
            // Verificar si tiene funciones activas
            if ($this->hasActiveShowtimes($roomId)) {
                throw new \Exception('No se puede eliminar una sala con funciones activas');
            }

            $this->beginTransaction();

            // Eliminar asientos
            $this->seatModel->deleteRoomSeats($roomId);

            // Eliminar sala
            $deleted = $this->delete($roomId);

            $this->commit();

            $this->logger->info('Room deleted with seats', ['room_id' => $roomId]);

            return $deleted;

        } catch (\Exception $e) {
            $this->rollBack();
            $this->logger->exception($e, ['room_id' => $roomId]);
            throw $e;
        }
    }

    /**
     * Actualizar sala y regenerar asientos
     */
    public function updateRoomWithSeats(int $roomId, array $roomData, bool $regenerateSeats = false): array
    {
        try {
            $this->beginTransaction();

            // Actualizar sala
            $room = $this->update($roomId, $roomData);

            // Regenerar asientos si es necesario
            if ($regenerateSeats && isset($roomData['rows']) && isset($roomData['columns'])) {
                // Eliminar asientos existentes
                $this->seatModel->deleteRoomSeats($roomId);

                // Crear nuevos asientos
                $seatsCreated = $this->seatModel->createSeatsForRoom(
                    $roomId,
                    $roomData['rows'],
                    $roomData['columns']
                );

                $room['seats_regenerated'] = $seatsCreated;
            }

            $this->commit();

            $this->logger->info('Room updated', [
                'room_id' => $roomId,
                'regenerated_seats' => $regenerateSeats
            ]);

            return $room;

        } catch (\Exception $e) {
            $this->rollBack();
            $this->logger->exception($e, ['room_id' => $roomId]);
            throw $e;
        }
    }

    /**
     * Buscar por nombre
     */
    public function findByName(string $name): ?array
    {
        try {
            return $this->roomModel->findByName($name);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['name' => $name]);
            throw $e;
        }
    }

    /**
     * Verificar disponibilidad en horario
     */
    public function isAvailable(int $roomId, string $date, string $time): bool
    {
        try {
            return $this->roomModel->isAvailable($roomId, $date, $time);
        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'room_id' => $roomId,
                'date' => $date,
                'time' => $time
            ]);
            throw $e;
        }
    }
}