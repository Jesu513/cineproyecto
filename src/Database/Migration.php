<?php
// ============================================
// src/Database/Migration.php
// Sistema de migraciones de base de datos
// ============================================

namespace App\Database;

use PDO;
use Exception;

class Migration
{
    private Connection $connection;
    private string $migrationsPath;
    private string $migrationsTable = 'migrations';

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->migrationsPath = __DIR__ . '/../../database/migrations/';
        $this->createMigrationsTable();
    }

    /**
     * Crear tabla de migraciones si no existe
     */
    private function createMigrationsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
            id INT PRIMARY KEY AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->connection->getConnection()->exec($sql);
    }

    /**
     * Ejecutar todas las migraciones pendientes
     */
    public function migrate(): array
    {
        $executed = [];
        $pending = $this->getPendingMigrations();

        if (empty($pending)) {
            echo "No pending migrations.\n";
            return [];
        }

        $batch = $this->getNextBatchNumber();

        foreach ($pending as $migration) {
            try {
                echo "Migrating: {$migration}\n";
                
                $this->connection->beginTransaction();
                
                $this->executeMigrationFile($migration);
                $this->recordMigration($migration, $batch);
                
                $this->connection->commit();
                
                $executed[] = $migration;
                echo "Migrated: {$migration}\n";
                
            } catch (Exception $e) {
                $this->connection->rollBack();
                echo "Error migrating {$migration}: " . $e->getMessage() . "\n";
                break;
            }
        }

        return $executed;
    }

    /**
     * Revertir última migración o lote
     */
    public function rollback(int $steps = 1): array
    {
        $rolledBack = [];
        $migrations = $this->getLastBatchMigrations($steps);

        if (empty($migrations)) {
            echo "Nothing to rollback.\n";
            return [];
        }

        foreach ($migrations as $migration) {
            try {
                echo "Rolling back: {$migration['migration']}\n";
                
                $this->connection->beginTransaction();
                
                // Aquí deberías ejecutar el método down() si tienes migraciones reversibles
                $this->removeMigration($migration['migration']);
                
                $this->connection->commit();
                
                $rolledBack[] = $migration['migration'];
                echo "Rolled back: {$migration['migration']}\n";
                
            } catch (Exception $e) {
                $this->connection->rollBack();
                echo "Error rolling back {$migration['migration']}: " . $e->getMessage() . "\n";
                break;
            }
        }

        return $rolledBack;
    }

    /**
     * Revertir todas las migraciones
     */
    public function reset(): array
    {
        $rolledBack = [];
        $migrations = $this->getExecutedMigrations();

        foreach (array_reverse($migrations) as $migration) {
            try {
                echo "Rolling back: {$migration['migration']}\n";
                
                $this->connection->beginTransaction();
                $this->removeMigration($migration['migration']);
                $this->connection->commit();
                
                $rolledBack[] = $migration['migration'];
                echo "Rolled back: {$migration['migration']}\n";
                
            } catch (Exception $e) {
                $this->connection->rollBack();
                echo "Error rolling back {$migration['migration']}: " . $e->getMessage() . "\n";
                break;
            }
        }

        return $rolledBack;
    }

    /**
     * Refrescar todas las migraciones (reset + migrate)
     */
    public function refresh(): array
    {
        echo "Refreshing database...\n";
        $this->reset();
        return $this->migrate();
    }

    /**
     * Obtener estado de las migraciones
     */
    public function status(): array
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $executedNames = array_column($executed, 'migration');

        $status = [];
        foreach ($all as $migration) {
            $status[] = [
                'migration' => $migration,
                'status' => in_array($migration, $executedNames) ? 'Executed' : 'Pending',
                'batch' => $this->getMigrationBatch($migration)
            ];
        }

        return $status;
    }

    /**
     * Obtener migraciones pendientes
     */
    private function getPendingMigrations(): array
    {
        $all = $this->getAllMigrationFiles();
        $executed = $this->getExecutedMigrations();
        $executedNames = array_column($executed, 'migration');

        return array_values(array_diff($all, $executedNames));
    }

    /**
     * Obtener todos los archivos de migración
     */
    private function getAllMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return [];
        }

        $files = scandir($this->migrationsPath);
        $migrations = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    /**
     * Obtener migraciones ejecutadas
     */
    private function getExecutedMigrations(): array
    {
        $sql = "SELECT * FROM {$this->migrationsTable} ORDER BY id ASC";
        return $this->connection->fetchAll($sql);
    }

    /**
     * Obtener migraciones del último lote
     */
    private function getLastBatchMigrations(int $steps = 1): array
    {
        $sql = "SELECT * FROM {$this->migrationsTable} 
                WHERE batch >= (SELECT MAX(batch) FROM {$this->migrationsTable}) - ? + 1
                ORDER BY id DESC";
        
        return $this->connection->fetchAll($sql, [$steps]);
    }

    /**
     * Obtener siguiente número de lote
     */
    private function getNextBatchNumber(): int
    {
        $sql = "SELECT MAX(batch) as max_batch FROM {$this->migrationsTable}";
        $result = $this->connection->fetchOne($sql);
        return ($result['max_batch'] ?? 0) + 1;
    }

    /**
     * Obtener lote de una migración
     */
    private function getMigrationBatch(string $migration): ?int
    {
        $sql = "SELECT batch FROM {$this->migrationsTable} WHERE migration = ? LIMIT 1";
        $result = $this->connection->fetchOne($sql, [$migration]);
        return $result['batch'] ?? null;
    }

    /**
     * Ejecutar archivo de migración
     */
    private function executeMigrationFile(string $filename): void
    {
        $filepath = $this->migrationsPath . $filename;

        if (!file_exists($filepath)) {
            throw new Exception("Migration file not found: {$filename}");
        }

        $sql = file_get_contents($filepath);
        
        // Dividir por punto y coma para ejecutar múltiples statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt)
        );

        foreach ($statements as $statement) {
            $this->connection->getConnection()->exec($statement);
        }
    }

    /**
     * Registrar migración ejecutada
     */
    private function recordMigration(string $migration, int $batch): void
    {
        $sql = "INSERT INTO {$this->migrationsTable} (migration, batch) VALUES (?, ?)";
        $this->connection->query($sql, [$migration, $batch]);
    }

    /**
     * Eliminar registro de migración
     */
    private function removeMigration(string $migration): void
    {
        $sql = "DELETE FROM {$this->migrationsTable} WHERE migration = ?";
        $this->connection->query($sql, [$migration]);
    }

    /**
     * Crear nuevo archivo de migración
     */
    public function create(string $name): string
    {
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_{$name}.sql";
        $filepath = $this->migrationsPath . $filename;

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $template = "-- Migration: {$name}\n";
        $template .= "-- Created at: " . date('Y-m-d H:i:s') . "\n\n";
        $template .= "-- Write your migration SQL here\n\n";

        file_put_contents($filepath, $template);

        echo "Migration created: {$filename}\n";
        return $filename;
    }

    /**
     * Verificar si hay migraciones pendientes
     */
    public function hasPendingMigrations(): bool
    {
        return !empty($this->getPendingMigrations());
    }

    /**
     * Imprimir estado de migraciones
     */
    public function printStatus(): void
    {
        $status = $this->status();

        if (empty($status)) {
            echo "No migrations found.\n";
            return;
        }

        echo "\n";
        echo str_pad('Migration', 60) . " | " . str_pad('Status', 10) . " | Batch\n";
        echo str_repeat('-', 80) . "\n";

        foreach ($status as $item) {
            echo str_pad($item['migration'], 60) . " | ";
            echo str_pad($item['status'], 10) . " | ";
            echo ($item['batch'] ?? '-') . "\n";
        }

        echo "\n";
    }
}