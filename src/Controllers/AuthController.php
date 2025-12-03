<?php
// ============================================
// src/Controllers/AuthController.php
// Controlador de autenticación
// ============================================

namespace App\Controllers;

use App\Services\AuthService;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;
use Exception;

class AuthController
{
    private AuthService $authService;
    private Logger $logger;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->logger = new Logger();
    }

    /**
     * POST /api/auth/register
     * Registrar nuevo usuario
     */
    public function register(): void
    {
        try {
            // Obtener datos del request
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar datos
            $validator = Validator::make($data, [
                'name' => 'required|min:3|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'phone' => 'phone'
            ], [
                'name.required' => 'El nombre es requerido',
                'name.min' => 'El nombre debe tener al menos 3 caracteres',
                'email.required' => 'El email es requerido',
                'email.email' => 'El email no es válido',
                'email.unique' => 'El email ya está registrado',
                'password.required' => 'La contraseña es requerida',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres',
                'phone.phone' => 'El teléfono no es válido'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Validar contraseña segura
            if (!Validator::isStrongPassword($data['password'])) {
                Response::validationError([
                    'password' => ['La contraseña debe contener mayúsculas, minúsculas y números']
                ]);
                return;
            }

            // Registrar usuario
            $result = $this->authService->register($validator->validated());

            Response::created($result, 'Usuario registrado exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/login
     * Iniciar sesión
     */
    public function login(): void
    {
        try {
            // Obtener datos del request
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar datos
            $validator = Validator::make($data, [
                'email' => 'required|email',
                'password' => 'required'
            ], [
                'email.required' => 'El email es requerido',
                'email.email' => 'El email no es válido',
                'password.required' => 'La contraseña es requerida'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Iniciar sesión
            $result = $this->authService->login(
                $data['email'],
                $data['password']
            );

            Response::success($result, 'Inicio de sesión exitoso');

        } catch (Exception $e) {
            $this->logger->warning('Login failed', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * POST /api/auth/logout
     * Cerrar sesión
     */
    public function logout(): void
    {
        try {
            // Obtener usuario del token (agregado por AuthMiddleware)
            $userId = $_REQUEST['auth_user_id'] ?? null;

            if (!$userId) {
                Response::unauthorized('No autenticado');
                return;
            }

            // Cerrar sesión
            $this->authService->logout($userId);

            Response::success(null, 'Sesión cerrada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/auth/me
     * Obtener usuario actual
     */
    public function me(): void
    {
        try {
            // Obtener usuario del token (agregado por AuthMiddleware)
            $userId = $_REQUEST['auth_user_id'] ?? null;

            if (!$userId) {
                Response::unauthorized('No autenticado');
                return;
            }

            // Obtener datos del usuario
            $user = $this->authService->getCurrentUser(
                $this->extractToken()
            );

            if (!$user) {
                Response::notFound('Usuario no encontrado');
                return;
            }

            Response::success($user, 'Usuario actual');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/refresh
     * Refrescar token de acceso
     */
    public function refresh(): void
    {
        try {
            // Obtener refresh token
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['refresh_token'])) {
                Response::validationError([
                    'refresh_token' => ['El refresh token es requerido']
                ]);
                return;
            }

            // Refrescar token
            $result = $this->authService->refreshToken($data['refresh_token']);

            Response::success($result, 'Token refrescado exitosamente');

        } catch (Exception $e) {
            $this->logger->warning('Token refresh failed', [
                'error' => $e->getMessage()
            ]);
            Response::unauthorized($e->getMessage());
        }
    }

    /**
     * POST /api/auth/change-password
     * Cambiar contraseña
     */
    public function changePassword(): void
    {
        try {
            $userId = $_REQUEST['auth_user_id'] ?? null;

            if (!$userId) {
                Response::unauthorized('No autenticado');
                return;
            }

            // Obtener datos
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar datos
            $validator = Validator::make($data, [
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed'
            ], [
                'current_password.required' => 'La contraseña actual es requerida',
                'new_password.required' => 'La nueva contraseña es requerida',
                'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres',
                'new_password.confirmed' => 'Las contraseñas no coinciden'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Validar contraseña segura
            if (!Validator::isStrongPassword($data['new_password'])) {
                Response::validationError([
                    'new_password' => ['La contraseña debe contener mayúsculas, minúsculas y números']
                ]);
                return;
            }

            // Cambiar contraseña
            $this->authService->changePassword(
                $userId,
                $data['current_password'],
                $data['new_password']
            );

            Response::success(null, 'Contraseña actualizada exitosamente');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/auth/forgot-password
     * Solicitar reseteo de contraseña
     */
    public function forgotPassword(): void
    {
        try {
            // Obtener datos
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar email
            $validator = Validator::make($data, [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                Response::validationError($validator->errors());
                return;
            }

            // Solicitar reseteo
            $this->authService->requestPasswordReset($data['email']);

            Response::success(null, 'Se ha enviado un email con instrucciones para resetear tu contraseña');

        } catch (Exception $e) {
            $this->logger->exception($e);
            Response::error($e->getMessage(), 400);
        }
    }

    /**
     * Extraer token del header Authorization
     */
    private function extractToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader) {
            return null;
        }

        // Formato: "Bearer token_aqui"
        $parts = explode(' ', $authHeader);
        
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer') {
            return null;
        }

        return $parts[1];
    }
}