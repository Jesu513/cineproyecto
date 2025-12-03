<?php
// ============================================
// src/Middleware/AuthMiddleware.php
// Middleware para verificar autenticación JWT
// ============================================

namespace App\Middleware;

use App\Services\AuthService;
use App\Utils\Response;
use App\Utils\Logger;
use Exception;

class AuthMiddleware
{
    private AuthService $authService;
    private Logger $logger;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->logger = new Logger();
    }

    /**
     * Verificar autenticación
     */
    public function handle(): bool
    {
        try {
            // Extraer token del header
            $token = $this->extractToken();

            if (!$token) {
                $this->logger->security('Missing authentication token');
                Response::unauthorized('Token de autenticación no proporcionado');
                return false;
            }

            // Validar y decodificar token
            $payload = $this->authService->decodeToken($token);

            // Verificar que sea un access token
            if (!isset($payload->type) || $payload->type !== 'access') {
                $this->logger->security('Invalid token type', 'warning', [
                    'type' => $payload->type ?? 'unknown'
                ]);
                Response::unauthorized('Tipo de token inválido');
                return false;
            }

            // Verificar que el usuario existe y está activo
            $user = $this->authService->getCurrentUser($token);

            if (!$user) {
                $this->logger->security('User not found for token');
                Response::unauthorized('Usuario no encontrado');
                return false;
            }

            if (!$user['is_active']) {
                $this->logger->security('Inactive user attempted access', 'warning', [
                    'user_id' => $user['id']
                ]);
                Response::forbidden('Usuario inactivo');
                return false;
            }

            // Agregar información del usuario al request
            $_REQUEST['auth_user_id'] = $user['id'];
            $_REQUEST['auth_user_email'] = $user['email'];
            $_REQUEST['auth_user_role'] = $user['role'];
            $_REQUEST['auth_user'] = $user;

            return true;

        } catch (Exception $e) {
            $this->logger->security('Authentication failed', 'warning', [
                'error' => $e->getMessage()
            ]);
            Response::unauthorized('Token inválido o expirado');
            return false;
        }
    }

    /**
     * Verificar rol específico
     */
    public function requireRole(string|array $roles): bool
    {
        // Primero verificar autenticación
        if (!$this->handle()) {
            return false;
        }

        $userRole = $_REQUEST['auth_user_role'] ?? null;

        if (!$userRole) {
            Response::forbidden('Rol no disponible');
            return false;
        }

        $allowedRoles = is_array($roles) ? $roles : [$roles];

        if (!in_array($userRole, $allowedRoles)) {
            $this->logger->security('Insufficient permissions', 'warning', [
                'user_id' => $_REQUEST['auth_user_id'] ?? null,
                'user_role' => $userRole,
                'required_roles' => $allowedRoles
            ]);
            Response::forbidden('No tienes permisos para realizar esta acción');
            return false;
        }

        return true;
    }

    /**
     * Verificar que sea admin
     */
    public function requireAdmin(): bool
    {
        return $this->requireRole('admin');
    }

    /**
     * Verificar que sea staff o admin
     */
    public function requireStaff(): bool
    {
        return $this->requireRole(['admin', 'staff']);
    }

    /**
     * Verificar que sea customer (o superior)
     */
    public function requireCustomer(): bool
    {
        return $this->requireRole(['customer', 'staff', 'admin']);
    }

    /**
     * Extraer token del header Authorization
     */
    private function extractToken(): ?string
    {
        // Obtener todos los headers
        $headers = getallheaders();
        
        if (!$headers) {
            // Fallback para servidores que no soportan getallheaders()
            $headers = $this->getAllHeadersFallback();
        }

        // Buscar header Authorization (case-insensitive)
        $authHeader = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }

        if (!$authHeader) {
            return null;
        }

        // Formato esperado: "Bearer token_aqui"
        $parts = explode(' ', $authHeader);

        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            return null;
        }

        return $parts[1];
    }

    /**
     * Fallback para obtener headers
     */
    private function getAllHeadersFallback(): array
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$header] = $value;
            }
        }

        return $headers;
    }

    /**
     * Método estático para facilitar uso
     */
    public static function check(): bool
    {
        $middleware = new self();
        return $middleware->handle();
    }

    /**
     * Verificación estática de rol
     */
    public static function checkRole(string|array $roles): bool
    {
        $middleware = new self();
        return $middleware->requireRole($roles);
    }

    /**
     * Verificación estática de admin
     */
    public static function checkAdmin(): bool
    {
        $middleware = new self();
        return $middleware->requireAdmin();
    }

    /**
     * Verificación estática de staff
     */
    public static function checkStaff(): bool
    {
        $middleware = new self();
        return $middleware->requireStaff();
    }
}