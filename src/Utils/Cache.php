<?php
// ============================================
// Cache.php — Cache simple basado en archivos
// ============================================

namespace App\Utils;

class Cache
{
    private string $path;

    public function __construct()
    {
        $this->path = __DIR__ . '/../../storage/cache/';

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    private function file(string $key): string
    {
        return $this->path . md5($key) . '.cache';
    }

    public function set(string $key, mixed $data, int $ttl = 3600): bool
    {
        $content = json_encode([
            'expires_at' => time() + $ttl,
            'data' => $data
        ]);

        return file_put_contents($this->file($key), $content) !== false;
    }

    public function get(string $key): mixed
    {
        $file = $this->file($key);

        if (!file_exists($file)) return null;

        $content = json_decode(file_get_contents($file), true);

        if ($content['expires_at'] < time()) {
            unlink($file);
            return null;
        }

        return $content['data'];
    }

    public function delete(string $key): bool
    {
        $file = $this->file($key);
        return file_exists($file) ? unlink($file) : false;
    }

    public function clear(): void
    {
        foreach (glob($this->path . '*.cache') as $file) {
            unlink($file);
        }
    }
}
