<?php
// ============================================
// config/redis.php
// Configuración de Redis para caché y sesiones
// ============================================

return [
    // Configuración de conexión
    'connection' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['REDIS_PORT'] ?? 6379),
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => (int)($_ENV['REDIS_DATABASE'] ?? 0),
        'timeout' => 5.0,
        'read_timeout' => 5.0,
    ],
    
    // Prefijos para diferentes tipos de datos
    'prefixes' => [
        'cache' => 'cinema:cache:',
        'session' => 'cinema:session:',
        'lock' => 'cinema:lock:',
        'rate_limit' => 'cinema:ratelimit:',
        'queue' => 'cinema:queue:',
        'booking_temp' => 'cinema:booking:temp:',
        'user_token' => 'cinema:token:',
    ],
    
    // Configuración de caché
    'cache' => [
        'enabled' => true,
        'default_ttl' => 3600, // 1 hora en segundos
        'ttl_by_type' => [
            'movies' => 1800,        // 30 minutos
            'showtimes' => 300,      // 5 minutos
            'seats' => 60,           // 1 minuto
            'user_session' => 7200,  // 2 horas
            'tmdb_data' => 86400,    // 24 horas
            'promotions' => 3600,    // 1 hora
        ],
    ],
    
    // Configuración de sesiones
    'session' => [
        'enabled' => true,
        'lifetime' => 7200, // 2 horas
        'cookie_name' => 'cinema_session',
    ],
    
    // Configuración de locks (bloqueos)
    'locks' => [
        'enabled' => true,
        'default_timeout' => 10, // segundos
        'retry_delay' => 100, // milisegundos
        'max_retries' => 10,
    ],
    
    // Configuración de rate limiting
    'rate_limit' => [
        'enabled' => true,
        'window' => 60, // ventana de tiempo en segundos
        'max_attempts' => 100, // intentos máximos por ventana
        'by_endpoint' => [
            'login' => [
                'window' => 300, // 5 minutos
                'max_attempts' => 5,
            ],
            'register' => [
                'window' => 3600, // 1 hora
                'max_attempts' => 3,
            ],
            'booking' => [
                'window' => 60,
                'max_attempts' => 10,
            ],
            'api_general' => [
                'window' => 60,
                'max_attempts' => 100,
            ],
        ],
    ],
    
    // Configuración de colas
    'queue' => [
        'enabled' => false,
        'default_queue' => 'default',
        'queues' => [
            'emails' => 'high',
            'notifications' => 'medium',
            'cleanup' => 'low',
        ],
    ],
    
    // Configuración de reservas temporales
    'temp_bookings' => [
        'enabled' => true,
        'ttl' => 600, // 10 minutos
        'prefix' => 'temp_booking:',
    ],
    
    // Configuración de blacklist de tokens
    'token_blacklist' => [
        'enabled' => true,
        'ttl' => 604800, // 7 días
        'prefix' => 'blacklist:token:',
    ],
    
    // Configuración de pub/sub
    'pubsub' => [
        'enabled' => false,
        'channels' => [
            'bookings' => 'cinema:bookings',
            'notifications' => 'cinema:notifications',
            'seats_update' => 'cinema:seats:update',
        ],
    ],
    
    // Persistencia
    'persistence' => [
        'enabled' => true,
        'strategy' => 'rdb', // rdb, aof, both
    ],
    
    // Clustering (para escalabilidad)
    'cluster' => [
        'enabled' => false,
        'nodes' => [
            // ['host' => '127.0.0.1', 'port' => 6379],
            // ['host' => '127.0.0.1', 'port' => 6380],
        ],
    ],
    
    // Sentinel (alta disponibilidad)
    'sentinel' => [
        'enabled' => false,
        'master_name' => 'cinema-master',
        'sentinels' => [
            // ['host' => '127.0.0.1', 'port' => 26379],
        ],
    ],
    
    // Configuración de serialización
    'serialization' => [
        'enabled' => true,
        'method' => 'json', // json, php, igbinary
    ],
    
    // Monitoreo y debugging
    'monitoring' => [
        'enabled' => false,
        'slow_log' => [
            'enabled' => true,
            'threshold' => 100, // milisegundos
        ],
    ],
    
    // Limpieza automática
    'cleanup' => [
        'enabled' => true,
        'schedule' => [
            'expired_bookings' => '0 */5 * * *', // Cada 5 minutos
            'old_sessions' => '0 2 * * *',       // Diario a las 2 AM
            'cache_flush' => '0 3 * * 0',        // Semanal domingos 3 AM
        ],
    ],
    
    // Opciones de conexión adicionales
    'options' => [
        'prefix' => 'cinema:',
        'serializer' => 'php',
        'compression' => false,
        'tcp_keepalive' => 60,
    ],
];