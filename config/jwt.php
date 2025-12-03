<?php
// ============================================
// config/jwt.php
// Configuración de JSON Web Tokens
// ============================================

return [
    // Clave secreta para firmar los tokens
    // IMPORTANTE: Debe ser una cadena aleatoria de al menos 32 caracteres
    'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production',
    
    // Algoritmo de encriptación
    // Opciones: HS256, HS384, HS512, RS256, RS384, RS512
    'algorithm' => 'HS256',
    
    // Tiempo de expiración del token de acceso (en segundos)
    // 3600 = 1 hora
    // 7200 = 2 horas
    // 86400 = 24 horas
    'access_token_expiration' => (int)($_ENV['JWT_EXPIRATION'] ?? 3600),
    
    // Tiempo de expiración del refresh token (en segundos)
    // 604800 = 7 días
    // 2592000 = 30 días
    'refresh_token_expiration' => (int)($_ENV['JWT_REFRESH_EXPIRATION'] ?? 604800),
    
    // Emisor del token (issuer)
    'issuer' => $_ENV['APP_URL'] ?? 'http://localhost',
    
    // Audiencia del token (audience)
    'audience' => $_ENV['APP_URL'] ?? 'http://localhost',
    
    // Tiempo de gracia antes de que el token sea válido (en segundos)
    // Útil para sincronización de relojes entre servidores
    'not_before' => 0,
    
    // Permitir refresh token
    'allow_refresh' => true,
    
    // Identificador del token en el header HTTP
    'header_name' => 'Authorization',
    
    // Prefijo del token en el header
    // Ejemplo: "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJh..."
    'token_prefix' => 'Bearer',
    
    // Blacklist de tokens (invalidar tokens antes de expiración)
    'blacklist' => [
        'enabled' => true,
        'grace_period' => 30, // segundos
    ],
];