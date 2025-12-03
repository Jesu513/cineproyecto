<?php
// =============================================
// SISTEMA CINE - BOOTSTRAP
// Carga Composer, variables .env, configuración
// =============================================

// 1. Autoload de Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die("❌ ERROR: No se encontró vendor/autoload.php. Ejecuta 'composer install'.");
}
require_once $autoloadPath;

// 2. Cargar variables de entorno (.env)
$dotenvPath = __DIR__ . '/../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
} else {
    die("❌ ERROR: Falta el archivo .env en la raíz del proyecto.");
}

// 3. Zona horaria
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Lima');

// 4. Configuración de errores (debug)
if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// 5. Inclusión de archivos de configuración
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/jwt.php';

// 6. Funciones globales opcionales
if (file_exists(__DIR__ . '/../src/helpers.php')) {
    require_once __DIR__ . '/../src/helpers.php';
}
