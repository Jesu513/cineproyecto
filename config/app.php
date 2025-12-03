<?php
// ============================================
// config/app.php
// Configuración general de la aplicación
// ============================================

return [
    // Información de la aplicación
    'name' => $_ENV['APP_NAME'] ?? 'Cinema Booking System',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'api_prefix' => '/api',
    'version' => '1.0.0',
    
    // Zona horaria
    'timezone' => 'America/Lima',
    
    // Configuración de sesiones
    'session' => [
        'lifetime' => 120, // minutos
        'cookie_name' => 'cinema_session',
        'cookie_path' => '/',
        'cookie_domain' => null,
        'cookie_secure' => false, // true en producción con HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    
    // Configuración de CORS
    'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => ['Authorization'],
        'max_age' => 86400,
        'supports_credentials' => true,
    ],
    
    // Configuración de rate limiting
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 100, // requests
        'window' => 60, // segundos
        'redis_key_prefix' => 'rate_limit:',
    ],
    
    // Configuración de archivos
    'uploads' => [
        'max_size' => (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 5242880), // 5MB en bytes
        'allowed_types' => explode(',', $_ENV['ALLOWED_IMAGE_TYPES'] ?? 'jpg,jpeg,png,webp'),
        'path' => __DIR__ . '/../public/uploads/',
        'url' => '/uploads/',
    ],
    
    // Configuración de reservas
    'booking' => [
        'temp_reservation_minutes' => (int)($_ENV['BOOKING_TEMP_RESERVATION_MINUTES'] ?? 10),
        'cancellation_hours' => (int)($_ENV['BOOKING_CANCELLATION_HOURS'] ?? 2),
        'max_seats_per_booking' => 10,
        'booking_code_length' => 8,
        'booking_code_prefix' => 'CIN',
    ],
    
    // Configuración de paginación
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    
    // Configuración de logs
    'logging' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/logs/',
        'level' => $_ENV['LOG_LEVEL'] ?? 'debug', // debug, info, warning, error
        'max_files' => 30, // días
    ],
    
    // Configuración de caché
    'cache' => [
        'enabled' => true,
        'driver' => 'redis', // redis, file
        'ttl' => 3600, // segundos
        'prefix' => 'cinema:',
    ],
    
    // URLs de frontend
    'frontend' => [
        'url' => $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000',
        'reset_password_url' => '/reset-password',
        'verify_email_url' => '/verify-email',
    ],
    
    // Seguridad
    'security' => [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_lowercase' => true,
        'password_require_numbers' => true,
        'password_require_special_chars' => false,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos en segundos
    ],
];