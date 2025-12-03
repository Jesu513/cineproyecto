<?php
// ============================================
// src/Database/QueryBuilder.php
// Constructor de queries SQL con método fluent
// ============================================

namespace App\Database;

use PDO;

class QueryBuilder
{
    private Connection $connection;
    private string $table = '';
    private array $select = ['*'];
    private array $where = [];
    private array $joins = [];
    private array $orderBy = [];
    private array $groupBy = [];
    private ?string $having = null;
    private ?int $limit = null;
    private ?int $offset = null;
    private array $bindings = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Establecer tabla
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * SELECT
     */
    public function select(string|array $columns = ['*']): self
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * WHERE
     */
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * OR WHERE
     */
    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * WHERE IN
     */
    public function whereIn(string $column, array $values): self
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * WHERE NULL
     */
    public function whereNull(string $column): self
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NULL',
            'value' => null
        ];

        return $this;
    }

    /**
     * WHERE NOT NULL
     */
    public function whereNotNull(string $column): self
    {
        $this->where[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => 'IS NOT NULL',
            'value' => null
        ];

        return $this;
    }

    /**
     * JOIN
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];

        return $this;
    }

    /**
     * LEFT JOIN
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * RIGHT JOIN
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * ORDER BY
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * GROUP BY
     */
    public function groupBy(string|array $columns): self
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * HAVING
     */
    public function having(string $condition): self
    {
        $this->having = $condition;
        return $this;
    }

    /**
     * LIMIT
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * OFFSET
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Construir query SELECT
     */
    private function buildSelectQuery(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->table;

        // JOINs
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // WHERE
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $conditions = [];
            
            foreach ($this->where as $index => $condition) {
                $placeholder = ":where_{$index}";
                
                if ($condition['operator'] === 'IN') {
                    $placeholders = [];
                    foreach ($condition['value'] as $i => $val) {
                        $ph = "{$placeholder}_{$i}";
                        $placeholders[] = $ph;
                        $this->bindings[$ph] = $val;
                    }
                    $inClause = '(' . implode(', ', $placeholders) . ')';
                    $conditions[] = ($index > 0 ? $condition['type'] . ' ' : '') . "{$condition['column']} IN {$inClause}";
                } elseif ($condition['operator'] === 'IS NULL' || $condition['operator'] === 'IS NOT NULL') {
                    $conditions[] = ($index > 0 ? $condition['type'] . ' ' : '') . "{$condition['column']} {$condition['operator']}";
                } else {
                    $conditions[] = ($index > 0 ? $condition['type'] . ' ' : '') . "{$condition['column']} {$condition['operator']} {$placeholder}";
                    $this->bindings[$placeholder] = $condition['value'];
                }
            }
            
            $sql .= implode(' ', $conditions);
        }

        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        // HAVING
        if ($this->having) {
            $sql .= ' HAVING ' . $this->having;
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /**
     * Ejecutar query y obtener todos los resultados
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        return $this->connection->fetchAll($sql, $this->bindings);
    }

    /**
     * Obtener primer resultado
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Contar resultados
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];
        
        $sql = $this->buildSelectQuery();
        $result = $this->connection->fetchOne($sql, $this->bindings);
        
        $this->select = $originalSelect;
        return (int)($result['count'] ?? 0);
    }

    /**
     * Verificar si existe
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * INSERT
     */
    public function insert(array $data): bool
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->connection->getConnection()->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * INSERT y obtener ID
     */
    public function insertGetId(array $data): string
    {
        $this->insert($data);
        return $this->connection->lastInsertId();
    }

    /**
     * UPDATE
     */
    public function update(array $data): int
    {
        $sets = [];
        foreach ($data as $column => $value) {
            $sets[] = "{$column} = :{$column}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets);

        // WHERE
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $conditions = [];
            
            foreach ($this->where as $index => $condition) {
                $placeholder = ":where_{$index}";
                $conditions[] = ($index > 0 ? $condition['type'] . ' ' : '') . "{$condition['column']} {$condition['operator']} {$placeholder}";
                $this->bindings[$placeholder] = $condition['value'];
            }
            
            $sql .= implode(' ', $conditions);
        }

        $bindings = array_merge($data, $this->bindings);
        $stmt = $this->connection->getConnection()->prepare($sql);
        $stmt->execute($bindings);
        
        return $stmt->rowCount();
    }

    /**
     * DELETE
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        // WHERE
        if (!empty($this->where)) {
            $sql .= ' WHERE ';
            $conditions = [];
            
            foreach ($this->where as $index => $condition) {
                $placeholder = ":where_{$index}";
                $conditions[] = ($index > 0 ? $condition['type'] . ' ' : '') . "{$condition['column']} {$condition['operator']} {$placeholder}";
                $this->bindings[$placeholder] = $condition['value'];
            }
            
            $sql .= implode(' ', $conditions);
        }

        $stmt = $this->connection->getConnection()->prepare($sql);
        $stmt->execute($this->bindings);
        
        return $stmt->rowCount();
    }

    /**
     * Truncate table
     */
    public function truncate(): bool
    {
        $sql = "TRUNCATE TABLE {$this->table}";
        return $this->connection->getConnection()->exec($sql) !== false;
    }

    /**
     * Raw query
     */
    public function raw(string $sql, array $bindings = []): array
    {
        return $this->connection->fetchAll($sql, $bindings);
    }

    /**
     * Reset query builder
     */
    public function reset(): self
    {
        $this->select = ['*'];
        $this->where = [];
        $this->joins = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->having = null;
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];
        
        return $this;
    }
}