<?php
// ============================================
// public/index.php
// Punto de entrada único del sistema
// Carga Router + Rutas + Middlewares
// ============================================

// Cargar configuración general y autoload
require_once __DIR__ . '/../config/bootstrap.php';

use App\Routes\Router;
use App\Middleware\CorsMiddleware;
use App\Utils\Logger;
use App\Utils\Response;

// Iniciar logger
$logger = Logger::getInstance();

// --------------------------------------------
// Capturar información del request
// --------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'], '?'); // Elimina parámetros
$uri = rtrim($uri, '/');

// Log de request
$logger->request([
    'method' => $method,
    'uri'    => $uri
]);

// Medir tiempo de ejecución
$start = microtime(true);

// --------------------------------------------
// Habilitar CORS automáticamente
// --------------------------------------------
CorsMiddleware::handle();

// Preflight Request (OPTIONS)
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --------------------------------------------
// Crear Router e incluir archivos de rutas
// --------------------------------------------
$router = new Router();

// Cargar rutas API
require_once __DIR__ . '/../src/Routes/api.php';

// Cargar rutas Web (si tuvieras páginas dinámicas)
require_once __DIR__ . '/../src/Routes/web.php';


// --------------------------------------------
// Intentar resolver la ruta
// --------------------------------------------
try {

    $matched = $router->run($method, $uri);

    if (!$matched) {
        Response::notFound("Ruta no encontrada: $uri");
        exit();
    }

} catch (Throwable $e) {

    // Log
    $logger->exception($e);

    // Error visible solo si APP_DEBUG=true
    $debug = getenv("APP_DEBUG") === 'true';

    Response::serverError(
        "Error interno del servidor",
        $debug ? [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine()
        ] : null
    );
    exit();
}

// Log final
$duration = microtime(true) - $start;
$logger->response(http_response_code(), $duration);
