<?php
// ============================================
// RateLimiter
// Limita cantidad de peticiones por IP
// ============================================

namespace App\Middleware;

use App\Services\CacheService;
use App\Utils\Response;

class RateLimiter
{
    private CacheService $cache;

    public function __construct()
    {
        $this->cache = new CacheService();
    }

    /**
     * @param string $key Identificador del endpoint
     * @param int $maxRequests Máximo permitido
     * @param int $seconds Ventana de tiempo
     */
    public function handle(string $key, int $maxRequests = 60, int $seconds = 60)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $cacheKey = "rate_{$key}_{$ip}";

        $current = $this->cache->get($cacheKey) ?? 0;

        if ($current >= $maxRequests) {
            return Response::json([
                "success" => false,
                "message" => "Demasiadas solicitudes. Intenta nuevamente en unos segundos."
            ], 429);
        }

        $this->cache->set($cacheKey, $current + 1, $seconds);

        return true;
    }
}
