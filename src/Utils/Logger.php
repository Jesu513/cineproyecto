<?php
namespace App\Utils;
class Logger
{
    private string $logPath;
    private string $logLevel;
    private array $levels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->logPath = $config['logging']['path'];
        $this->logLevel = $config['logging']['level'] ?? 'debug';

        // Crear directorio de logs si no existe
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * Log de debug
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log de información
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log de advertencia
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log de error
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log crítico
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log con nivel personalizado
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Verificar si el nivel debe ser logueado
        if (!$this->shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Formatear mensaje
        $logMessage = "[{$timestamp}] [{$levelUpper}] {$message}";
        
        // Agregar contexto si existe
        if (!empty($context)) {
            $logMessage .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        $logMessage .= PHP_EOL;

        // Escribir en archivo
        $this->writeToFile($level, $logMessage);
    }

    /**
     * Verificar si el nivel debe ser logueado
     */
    private function shouldLog(string $level): bool
    {
        $currentLevel = $this->levels[$this->logLevel] ?? 0;
        $messageLevel = $this->levels[$level] ?? 0;
        
        return $messageLevel >= $currentLevel;
    }

    /**
     * Escribir en archivo de log
     */
    private function writeToFile(string $level, string $message): void
    {
        $filename = $this->getLogFilename($level);
        $filepath = $this->logPath . $filename;

        // Rotar logs si es necesario
        $this->rotateIfNeeded($filepath);

        // Escribir en archivo
        file_put_contents($filepath, $message, FILE_APPEND | LOCK_EX);
    }

    /**
     * Obtener nombre del archivo de log
     */
    private function getLogFilename(string $level): string
    {
        $date = date('Y-m-d');
        
        // Logs separados por nivel para errores críticos
        if (in_array($level, ['error', 'critical'])) {
            return "{$level}-{$date}.log";
        }
        
        return "app-{$date}.log";
    }

    /**
     * Rotar logs si excede el tamaño
     */
    private function rotateIfNeeded(string $filepath): void
    {
        if (!file_exists($filepath)) {
            return;
        }

        // Rotar si el archivo es mayor a 10MB
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        if (filesize($filepath) > $maxSize) {
            $rotatedFile = $filepath . '.' . time() . '.bak';
            rename($filepath, $rotatedFile);
            
            // Comprimir archivo rotado
            if (function_exists('gzopen')) {
                $this->compressFile($rotatedFile);
            }
        }

        // Limpiar logs antiguos
        $this->cleanOldLogs();
    }

    /**
     * Comprimir archivo de log
     */
    private function compressFile(string $filepath): void
    {
        $gzFile = $filepath . '.gz';
        
        $fp = fopen($filepath, 'rb');
        $gzfp = gzopen($gzFile, 'wb9');
        
        if ($fp && $gzfp) {
            while (!feof($fp)) {
                gzwrite($gzfp, fread($fp, 1024 * 512));
            }
            
            fclose($fp);
            gzclose($gzfp);
            
            // Eliminar archivo original
            unlink($filepath);
        }
    }

    /**
     * Limpiar logs antiguos
     */
    private function cleanOldLogs(): void
    {
        $config = require __DIR__ . '/../../config/app.php';
        $maxFiles = $config['logging']['max_files'] ?? 30;
        
        $files = glob($this->logPath . '*.log*');
        
        if (count($files) <= $maxFiles) {
            return;
        }

        // Ordenar por fecha de modificación
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
        
        // Eliminar archivos más antiguos
        $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);
        
        foreach ($filesToDelete as $file) {
            unlink($file);
        }
    }

    /**
     * Log de excepción
     */
    public function exception(\Throwable $exception, array $context = []): void
    {
        $message = sprintf(
            "Exception: %s in %s:%d",
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );

        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        $this->error($message, $context);
    }

    /**
     * Log de petición HTTP
     */
    public function request(array $data = []): void
    {
        $context = [
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
            'timestamp' => microtime(true)
        ];

        $context = array_merge($context, $data);

        $this->info('HTTP Request', $context);
    }

    /**
     * Log de respuesta HTTP
     */
    public function response(int $statusCode, float $executionTime, array $data = []): void
    {
        $context = [
            'status_code' => $statusCode,
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'memory_usage' => $this->formatBytes(memory_get_peak_usage(true))
        ];

        $context = array_merge($context, $data);

        $level = $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info');
        $this->log($level, 'HTTP Response', $context);
    }

    /**
     * Log de query SQL
     */
    public function query(string $sql, array $bindings = [], float $executionTime = 0): void
    {
        $context = [
            'sql' => $sql,
            'bindings' => $bindings,
            'execution_time' => round($executionTime * 1000, 2) . 'ms'
        ];

        $this->debug('SQL Query', $context);
    }

    /**
     * Log de autenticación
     */
    public function auth(string $action, string $email, bool $success, array $data = []): void
    {
        $context = [
            'action' => $action,
            'email' => $email,
            'success' => $success,
            'ip' => $this->getClientIp()
        ];

        $context = array_merge($context, $data);

        $message = "Authentication {$action}: " . ($success ? 'SUCCESS' : 'FAILED');
        $level = $success ? 'info' : 'warning';
        
        $this->log($level, $message, $context);
    }

    /**
     * Log de operación de negocio
     */
    public function business(string $action, array $data = []): void
    {
        $context = [
            'action' => $action,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $this->getClientIp()
        ];

        $context = array_merge($context, $data);

        $this->info("Business Action: {$action}", $context);
    }

    /**
     * Log de seguridad
     */
    public function security(string $event, string $level = 'warning', array $data = []): void
    {
        $context = [
            'event' => $event,
            'ip' => $this->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'
        ];

        $context = array_merge($context, $data);

        $this->log($level, "Security Event: {$event}", $context);
    }

    /**
     * Obtener IP del cliente
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }

        return '0.0.0.0';
    }

    /**
     * Formatear bytes a unidad legible
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Obtener instancia estática (opcional)
     */
    private static ?Logger $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Métodos estáticos para facilitar uso
     */
    public static function logDebug(string $message, array $context = []): void
    {
        self::getInstance()->debug($message, $context);
    }

    public static function logInfo(string $message, array $context = []): void
    {
        self::getInstance()->info($message, $context);
    }

    public static function logWarning(string $message, array $context = []): void
    {
        self::getInstance()->warning($message, $context);
    }

    public static function logError(string $message, array $context = []): void
    {
        self::getInstance()->error($message, $context);
    }

    public static function logCritical(string $message, array $context = []): void
    {
        self::getInstance()->critical($message, $context);
    }

    public static function logException(\Throwable $exception, array $context = []): void
    {
        self::getInstance()->exception($exception, $context);
    }
}