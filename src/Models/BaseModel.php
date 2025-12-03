<?php
// ============================================
// src/Models/BaseModel.php
// Clase base para todos los modelos
// ============================================

namespace App\Models;

use App\Database\Connection;
use App\Database\QueryBuilder;

abstract class BaseModel
{
    protected Connection $connection;
    protected QueryBuilder $queryBuilder;
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;

    public function __construct()
    {
        $this->connection = Connection::getInstance();
        $this->queryBuilder = new QueryBuilder($this->connection);
    }

    /**
     * Obtener todos los registros
     */
    public function all(): array
    {
        return $this->queryBuilder
            ->table($this->table)
            ->get();
    }

    /**
     * Buscar por ID
     */
    public function find(int $id): ?array
    {
        $result = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $id)
            ->first();

        return $result ? $this->hideAttributes($result) : null;
    }

    /**
     * Buscar por campo
     */
    public function findBy(string $field, mixed $value): ?array
    {
        $result = $this->queryBuilder
            ->table($this->table)
            ->where($field, $value)
            ->first();

        return $result ? $this->hideAttributes($result) : null;
    }

    /**
     * Buscar múltiples registros por campo
     */
    public function findAllBy(string $field, mixed $value): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where($field, $value)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Crear nuevo registro
     */
    public function create(array $data): array
    {
        // Filtrar solo campos permitidos
        $data = $this->filterFillable($data);

        // Agregar timestamps
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $id = $this->queryBuilder
            ->table($this->table)
            ->insertGetId($data);

        return $this->find((int)$id) ?? [];
    }

    /**
     * Actualizar registro
     */
    public function update(int $id, array $data): ?array
    {
        // Filtrar solo campos permitidos
        $data = $this->filterFillable($data);

        // Agregar timestamp de actualización
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $id)
            ->update($data);

        return $this->find($id);
    }

    /**
     * Eliminar registro
     */
    public function delete(int $id): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $id)
            ->delete();

        return $affected > 0;
    }

    /**
     * Verificar si existe
     */
    public function exists(int $id): bool
    {
        return $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $id)
            ->exists();
    }

    /**
     * Contar registros
     */
    public function count(): int
    {
        return $this->queryBuilder
            ->table($this->table)
            ->count();
    }

    /**
     * Obtener con paginación
     */
    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        
        $results = $this->queryBuilder
            ->table($this->table)
            ->limit($perPage)
            ->offset($offset)
            ->get();

        $total = $this->count();

        return [
            'data' => array_map(fn($item) => $this->hideAttributes($item), $results),
            'pagination' => [
                'total' => $total,
                'count' => count($results),
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Obtener query builder
     */
    public function query(): QueryBuilder
    {
        return $this->queryBuilder->table($this->table);
    }

    /**
     * Filtrar solo campos permitidos (fillable)
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_filter(
            $data,
            fn($key) => in_array($key, $this->fillable),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Ocultar atributos sensibles
     */
    protected function hideAttributes(array $data): array
    {
        if (empty($this->hidden)) {
            return $this->castAttributes($data);
        }

        foreach ($this->hidden as $attribute) {
            unset($data[$attribute]);
        }

        return $this->castAttributes($data);
    }

    /**
     * Castear atributos según tipo definido
     */
    protected function castAttributes(array $data): array
    {
        if (empty($this->casts)) {
            return $data;
        }

        foreach ($this->casts as $attribute => $type) {
            if (!isset($data[$attribute])) {
                continue;
            }

            $data[$attribute] = match($type) {
                'int', 'integer' => (int)$data[$attribute],
                'float', 'double' => (float)$data[$attribute],
                'bool', 'boolean' => (bool)$data[$attribute],
                'string' => (string)$data[$attribute],
                'array', 'json' => is_string($data[$attribute]) 
                    ? json_decode($data[$attribute], true) 
                    : $data[$attribute],
                'date' => date('Y-m-d', strtotime($data[$attribute])),
                'datetime' => date('Y-m-d H:i:s', strtotime($data[$attribute])),
                default => $data[$attribute]
            };
        }

        return $data;
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Confirmar transacción
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Revertir transacción
     */
    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Convertir a array (para JSON)
     */
    public function toArray(array $data): array
    {
        return $this->hideAttributes($data);
    }

    /**
     * Convertir a JSON
     */
    public function toJson(array $data): string
    {
        return json_encode($this->toArray($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}