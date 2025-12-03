<?php
// ============================================
// src/Database/Connection.php
// Manejo de conexión PDO con patrón Singleton
// ============================================

namespace App\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?Connection $instance = null;
    private ?PDO $connection = null;
    private array $config;

    /**
     * Constructor privado para patrón Singleton
     */
    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/database.php';
        $this->connect();
    }

    /**
     * Obtener instancia única de Connection
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establecer conexión a la base de datos
     */
    private function connect(): void
    {
        try {
            $driver = $this->config['driver'];
            $host = $this->config['host'];
            $port = $this->config['port'];
            $database = $this->config['database'];
            $charset = $this->config['charset'];

            $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );

            // Log de conexión exitosa
            $this->log('Database connection established successfully');

        } catch (PDOException $e) {
            $this->log('Database connection failed: ' . $e->getMessage(), 'error');
            throw new PDOException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Obtener conexión PDO
     */
    public function getConnection(): PDO
    {
        // Verificar si la conexión está activa
        if ($this->connection === null) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Ejecutar query directa
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->log("Query failed: {$sql} - Error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Ejecutar query y obtener todos los resultados
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Ejecutar query y obtener un solo resultado
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Ejecutar query y obtener una sola columna
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn();
    }

    /**
     * Iniciar transacción
     */
    public function beginTransaction(): bool
    {
        if (!$this->connection->inTransaction()) {
            return $this->connection->beginTransaction();
        }
        return false;
    }

    /**
     * Confirmar transacción
     */
    public function commit(): bool
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->commit();
        }
        return false;
    }

    /**
     * Revertir transacción
     */
    public function rollBack(): bool
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->rollBack();
        }
        return false;
    }

    /**
     * Verificar si está en transacción
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * Obtener último ID insertado
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Verificar conexión
     */
    public function ping(): bool
    {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Reconectar si la conexión se perdió
     */
    public function reconnect(): void
    {
        $this->connection = null;
        $this->connect();
    }

    /**
     * Cerrar conexión
     */
    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * Escapar valores para prevenir SQL injection
     */
    public function quote(string $value): string
    {
        return $this->connection->quote($value);
    }

    /**
     * Obtener información de la conexión
     */
    public function getConnectionInfo(): array
    {
        return [
            'driver' => $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
            'connection_status' => $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS),
            'database' => $this->config['database'],
        ];
    }

    /**
     * Log de mensajes
     */
    private function log(string $message, string $level = 'info'): void
    {
        $logFile = __DIR__ . '/../../storage/logs/database.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        if (!is_dir(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Prevenir clonación
     */
    private function __clone() {}

    /**
     * Prevenir deserialización
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}