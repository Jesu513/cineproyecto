<?php
// ============================================
// CacheService
// Abstracción para uso de Redis y File Cache
// ============================================

namespace App\Services;

class CacheService
{
    private $redis;
    private bool $redisEnabled = false;
    private string $fileCachePath;

    public function __construct()
    {
        $this->fileCachePath = __DIR__ . '/../../storage/cache/';

        // Cargar configuración desde config/redis.php
        $config = require __DIR__ . '/../../config/redis.php';

        // Intentar conexión Redis
        try {
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port'], $config['timeout']);

            if (!empty($config['password'])) {
                $redis->auth($config['password']);
            }

            $this->redis = $redis;
            $this->redisEnabled = true;

        } catch (\Exception $e) {
            // Redis no disponible → usar File Cache
            $this->redisEnabled = false;
        }

        // Crear carpeta de cache si no existe
        if (!is_dir($this->fileCachePath)) {
            mkdir($this->fileCachePath, 0777, true);
        }
    }

    // ============================================
    // GUARDAR EN CACHE
    // ============================================

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $payload = json_encode([
            'expires_at' => time() + $ttl,
            'data' => $value
        ]);

        if ($this->redisEnabled) {
            return $this->redis->setex($key, $ttl, json_encode($value));
        }

        return file_put_contents($this->fileCachePath . $key . '.cache', $payload) !== false;
    }

    // ============================================
    // OBTENER DESDE CACHE
    // ============================================

    public function get(string $key): mixed
    {
        if ($this->redisEnabled) {
            $data = $this->redis->get($key);
            return $data ? json_decode($data, true) : null;
        }

        $file = $this->fileCachePath . $key . '.cache';

        if (!file_exists($file)) {
            return null;
        }

        $content = json_decode(file_get_contents($file), true);

        if ($content['expires_at'] < time()) {
            unlink($file);
            return null;
        }

        return $content['data'];
    }

    // ============================================
    // ELIMINAR DE CACHE
    // ============================================

    public function delete(string $key): bool
    {
        if ($this->redisEnabled) {
            return (bool) $this->redis->del($key);
        }

        $file = $this->fileCachePath . $key . '.cache';

        if (file_exists($file)) {
            return unlink($file);
        }

        return false;
    }

    // ============================================
    // LIMPIAR TODO EL CACHE
    // ============================================

    public function clearAll(): bool
    {
        if ($this->redisEnabled) {
            return $this->redis->flushAll();
        }

        $files = glob($this->fileCachePath . '*.cache');

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    // ============================================
    // CACHEAR AUTOMÁTICAMENTE UNA FUNCIÓN
    // ============================================

    public function remember(string $key, int $ttl, callable $callback)
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }
}
