<?php
// ============================================
// src/Utils/Response.php
// Clase para respuestas JSON estandarizadas
// ============================================

namespace App\Utils;

class Response
{
    /**
     * Enviar respuesta JSON exitosa
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): void {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta
        ], $statusCode);
    }

    /**
     * Enviar respuesta JSON de error
     */
    public static function error(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $errors = null,
        mixed $data = null
    ): void {
        self::send([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Respuesta de validación fallida
     */
    public static function validationError(
        array $errors,
        string $message = 'Validation failed'
    ): void {
        self::error($message, 422, $errors);
    }

    /**
     * Respuesta de recurso no encontrado
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Respuesta de no autorizado
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Respuesta de prohibido
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Respuesta de conflicto
     */
    public static function conflict(string $message = 'Conflict', mixed $errors = null): void
    {
        self::error($message, 409, $errors);
    }

    /**
     * Respuesta de recurso creado
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully',
        array $meta = []
    ): void {
        self::success($data, $message, 201, $meta);
    }

    /**
     * Respuesta de recurso actualizado
     */
    public static function updated(
        mixed $data = null,
        string $message = 'Resource updated successfully'
    ): void {
        self::success($data, $message, 200);
    }

    /**
     * Respuesta de recurso eliminado
     */
    public static function deleted(string $message = 'Resource deleted successfully'): void
    {
        self::success(null, $message, 200);
    }

    /**
     * Respuesta sin contenido
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit();
    }

    /**
     * Respuesta con paginación
     */
    public static function paginated(
        array $data,
        int $total,
        int $page,
        int $perPage,
        string $message = 'Success'
    ): void {
        $totalPages = ceil($total / $perPage);
        
        self::success($data, $message, 200, [
            'pagination' => [
                'total' => $total,
                'count' => count($data),
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    }

    /**
     * Respuesta de error del servidor
     */
    public static function serverError(
        string $message = 'Internal Server Error',
        mixed $debug = null
    ): void {
        $response = [
            'success' => false,
            'message' => $message
        ];

        // Incluir información de debug solo en desarrollo
        if ($_ENV['APP_DEBUG'] === 'true' && $debug !== null) {
            $response['debug'] = $debug;
        }

        self::send($response, 500);
    }

    /**
     * Respuesta de servicio no disponible
     */
    public static function serviceUnavailable(string $message = 'Service Unavailable'): void
    {
        self::error($message, 503);
    }

    /**
     * Respuesta de demasiadas peticiones (rate limit)
     */
    public static function tooManyRequests(
        string $message = 'Too many requests',
        int $retryAfter = 60
    ): void {
        header("Retry-After: {$retryAfter}");
        self::error($message, 429);
    }

    /**
     * Respuesta de método no permitido
     */
    public static function methodNotAllowed(
        string $message = 'Method not allowed',
        array $allowedMethods = []
    ): void {
        if (!empty($allowedMethods)) {
            header('Allow: ' . implode(', ', $allowedMethods));
        }
        self::error($message, 405);
    }

    /**
     * Respuesta de token inválido o expirado
     */
    public static function invalidToken(string $message = 'Invalid or expired token'): void
    {
        self::unauthorized($message);
    }

    /**
     * Respuesta de sesión expirada
     */
    public static function sessionExpired(string $message = 'Session expired'): void
    {
        self::unauthorized($message);
    }

    /**
     * Respuesta personalizada
     */
    public static function custom(array $data, int $statusCode = 200): void
    {
        self::send($data, $statusCode);
    }

    /**
     * Enviar respuesta JSON
     */
    private static function send(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        
        // Agregar timestamp
        $data['timestamp'] = date('Y-m-d H:i:s');
        
        // Agregar información de request en debug
        if ($_ENV['APP_DEBUG'] === 'true') {
            $data['request'] = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
                'ip' => self::getClientIp()
            ];
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Obtener IP del cliente
     */
    private static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }

        return '0.0.0.0';
    }

    /**
     * Redireccionar
     */
    public static function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: {$url}", true, $statusCode);
        exit();
    }

    /**
     * Enviar archivo para descarga
     */
    public static function download(string $filepath, string $filename = null): void
    {
        if (!file_exists($filepath)) {
            self::notFound('File not found');
            return;
        }

        $filename = $filename ?? basename($filepath);
        $mimeType = mime_content_type($filepath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        readfile($filepath);
        exit();
    }

    /**
     * Enviar respuesta con headers personalizados
     */
    public static function withHeaders(array $headers, array $data, int $statusCode = 200): void
    {
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        self::send($data, $statusCode);
    }

    /** 
     * Respuesta de búsqueda sin resultados
     */
    public static function noResults(string $message = 'No results found'): void
    {
        self::success([], $message, 200);
    }

    /**
     * Respuesta de operación en proceso
     */
    public static function processing(
        string $message = 'Request is being processed',
        mixed $data = null
    ): void {
        self::success($data, $message, 202);
    }
}