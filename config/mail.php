<?php
// ============================================
// config/mail.php
// Configuración de PHPMailer para envío de correos
// ============================================

return [
    // Driver de correo: smtp, sendmail, mail
    'driver' => 'smtp',
    
    // Configuración SMTP
    'smtp' => [
        'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
        'port' => (int)($_ENV['MAIL_PORT'] ?? 587),
        'username' => $_ENV['MAIL_USERNAME'] ?? '',
        'password' => $_ENV['MAIL_PASSWORD'] ?? '',
        'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls', // tls o ssl
        'auth' => true,
        'auto_tls' => true,
        'timeout' => 30,
    ],
    
    // Remitente por defecto
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@cinema.com',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'Cinema Booking System',
    ],
    
    // Responder a (reply-to)
    'reply_to' => [
        'address' => $_ENV['MAIL_REPLY_TO_ADDRESS'] ?? 'support@cinema.com',
        'name' => $_ENV['MAIL_REPLY_TO_NAME'] ?? 'Cinema Support',
    ],
    
    // Configuración de charset y encoding
    'charset' => 'UTF-8',
    'encoding' => 'base64',
    
    // Formato de correo: text o html
    'html' => true,
    
    // Habilitar debug de PHPMailer
    // 0 = Desactivado
    // 1 = Errores y mensajes
    // 2 = Mensajes solamente
    // 3 = Mensajes + comandos SMTP
    // 4 = Low-level data output
    'debug' => (int)($_ENV['MAIL_DEBUG'] ?? 0),
    
    // Plantillas de correo
    'templates' => [
        'path' => __DIR__ . '/../resources/email-templates/',
        'booking_confirmation' => 'booking-confirmation.html',
        'booking_reminder' => 'booking-reminder.html',
        'booking_cancelled' => 'booking-cancelled.html',
        'password_reset' => 'password-reset.html',
        'welcome' => 'welcome.html',
        'promotion' => 'promotion.html',
    ],
    
    // Configuración de attachments
    'attachments' => [
        'max_size' => 10485760, // 10MB
        'allowed_types' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],
    
    // Cola de correos (para envío asíncrono)
    'queue' => [
        'enabled' => false,
        'connection' => 'redis',
        'queue_name' => 'emails',
    ],
    
    // Registro de correos enviados
    'log' => [
        'enabled' => true,
        'path' => __DIR__ . '/../storage/logs/mail.log',
    ],
];