<?php
// ============================================
// src/Repositories/BaseRepository.php
// Repositorio base con lógica común
// ============================================

namespace App\Repositories;

use App\Models\BaseModel;
use App\Utils\Logger;

abstract class BaseRepository
{
    protected BaseModel $model;
    protected Logger $logger;

    public function __construct(BaseModel $model)
    {
        $this->model = $model;
        $this->logger = new Logger();
    }

    /**
     * Obtener todos los registros
     */
    public function all(): array
    {
        try {
            return $this->model->all();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Buscar por ID
     */
    public function find(int $id): ?array
    {
        try {
            return $this->model->find($id);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Buscar por campo específico
     */
    public function findBy(string $field, mixed $value): ?array
    {
        try {
            return $this->model->findBy($field, $value);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Buscar múltiples por campo
     */
    public function findAllBy(string $field, mixed $value): array
    {
        try {
            return $this->model->findAllBy($field, $value);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Crear nuevo registro
     */
    public function create(array $data): array
    {
        try {
            $this->logger->debug('Creating record', ['data' => $data]);
            $result = $this->model->create($data);
            $this->logger->info('Record created', ['id' => $result['id'] ?? null]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->exception($e, ['data' => $data]);
            throw $e;
        }
    }

    /**
     * Actualizar registro
     */
    public function update(int $id, array $data): ?array
    {
        try {
            $this->logger->debug('Updating record', ['id' => $id, 'data' => $data]);
            $result = $this->model->update($id, $data);
            $this->logger->info('Record updated', ['id' => $id]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->exception($e, ['id' => $id, 'data' => $data]);
            throw $e;
        }
    }

    /**
     * Eliminar registro
     */
    public function delete(int $id): bool
    {
        try {
            $this->logger->debug('Deleting record', ['id' => $id]);
            $result = $this->model->delete($id);
            $this->logger->info('Record deleted', ['id' => $id, 'success' => $result]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->exception($e, ['id' => $id]);
            throw $e;
        }
    }

    /**
     * Verificar si existe
     */
    public function exists(int $id): bool
    {
        try {
            return $this->model->exists($id);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Contar registros
     */
    public function count(): int
    {
        try {
            return $this->model->count();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Paginación
     */
    public function paginate(int $page = 1, int $perPage = 20): array
    {
        try {
            return $this->model->paginate($page, $perPage);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener query builder
     */
    public function query()
    {
        return $this->model->query();
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction(): bool
    {
        return $this->model->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit(): bool
    {
        return $this->model->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollBack(): bool
    {
        return $this->model->rollBack();
    }
}