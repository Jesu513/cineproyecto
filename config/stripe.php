<?php
// ============================================
// config/stripe.php
// Configuración de Stripe para procesamiento de pagos
// ============================================

return [
    // Claves de API
    // Obtener en: https://dashboard.stripe.com/apikeys
    'public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
    'secret_key' => $_ENV['STRIPE_SECRET_KEY'] ?? '',
    
    // Webhook secret para verificar eventos
    // Obtener en: https://dashboard.stripe.com/webhooks
    'webhook_secret' => $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '',
    
    // Versión de la API de Stripe
    'api_version' => '2023-10-16',
    
    // Moneda
    // Códigos ISO: usd, pen, eur, etc.
    'currency' => $_ENV['STRIPE_CURRENCY'] ?? 'pen',
    
    // País
    'country' => 'PE',
    
    // Configuración de Payment Intents
    'payment_intent' => [
        // Métodos de pago permitidos
        'payment_method_types' => ['card'],
        
        // Captura automática o manual
        'capture_method' => 'automatic', // automatic o manual
        
        // Configuración de confirmación
        'confirmation_method' => 'automatic', // automatic o manual
        
        // Descripción por defecto
        'statement_descriptor' => 'CINEMA BOOKING',
        
        // Metadata adicional
        'metadata' => [
            'source' => 'cinema_booking_system',
        ],
    ],
    
    // Configuración de webhooks
    'webhooks' => [
        'enabled' => true,
        'tolerance' => 300, // Tolerancia en segundos para timestamp
        
        // Eventos a escuchar
        'events' => [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'charge.succeeded',
            'charge.failed',
            'charge.refunded',
        ],
    ],
    
    // Configuración de reembolsos
    'refunds' => [
        'enabled' => true,
        'reason_required' => true,
        'reasons' => [
            'duplicate' => 'Pago duplicado',
            'fraudulent' => 'Pago fraudulento',
            'requested_by_customer' => 'Solicitado por el cliente',
            'other' => 'Otro',
        ],
    ],
    
    // Configuración de tarjetas
    'cards' => [
        // Validación de CVC
        'require_cvc' => true,
        
        // Validación de código postal
        'require_postal_code' => false,
        
        // Guardar tarjetas para uso futuro
        'save_payment_method' => false,
    ],
    
    // URLs de redirección
    'urls' => [
        'success' => $_ENV['FRONTEND_URL'] . '/payment/success',
        'cancel' => $_ENV['FRONTEND_URL'] . '/payment/cancel',
    ],
    
    // Límites de transacción
    'limits' => [
        'min_amount' => 100, // Monto mínimo en centavos (1.00 PEN)
        'max_amount' => 1000000, // Monto máximo en centavos (10,000.00 PEN)
    ],
    
    // Configuración de 3D Secure
    'three_d_secure' => [
        'enabled' => true,
        'version' => 2,
    ],
    
    // Timeout de conexión
    'timeout' => 30,
    
    // Número de reintentos en caso de fallo
    'max_network_retries' => 3,
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
    ],
];