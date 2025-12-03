<?php
// ============================================
// src/Services/AuthService.php
// Servicio de autenticación con JWT
// ============================================

namespace App\Services;

use App\Repositories\UserRepository;
use App\Utils\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthService
{
    private UserRepository $userRepository;
    private Logger $logger;
    private array $jwtConfig;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->logger = new Logger();
        $this->jwtConfig = require __DIR__ . '/../../config/jwt.php';
    }

    /**
     * Registrar nuevo usuario
     */
    public function register(array $data): array
    {
        try {
            // Verificar si el email ya existe
            if ($this->userRepository->emailExists($data['email'])) {
                throw new Exception('El email ya está registrado');
            }

            // Crear usuario
            $user = $this->userRepository->createUser($data);

            // Generar tokens
            $tokens = $this->generateTokens($user);

            $this->logger->auth('register', $data['email'], true, [
                'user_id' => $user['id']
            ]);

            return [
                'user' => $user,
                'tokens' => $tokens
            ];

        } catch (Exception $e) {
            $this->logger->auth('register', $data['email'] ?? 'unknown', false, [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Iniciar sesión
     */
    public function login(string $email, string $password): array
    {
        try {
            // Buscar usuario por email (con password)
            $user = $this->userRepository->findByEmailWithPassword($email);

            if (!$user) {
                throw new Exception('Credenciales inválidas');
            }

            // Verificar contraseña
            if (!$this->userRepository->verifyPassword($password, $user['password'])) {
                $this->logger->auth('login', $email, false, ['reason' => 'invalid_password']);
                throw new Exception('Credenciales inválidas');
            }

            // Verificar si está activo
            if (!$user['is_active']) {
                $this->logger->auth('login', $email, false, ['reason' => 'user_inactive']);
                throw new Exception('Usuario inactivo');
            }

            // Remover password del array antes de devolver
            unset($user['password']);

            // Generar tokens
            $tokens = $this->generateTokens($user);

            $this->logger->auth('login', $email, true, ['user_id' => $user['id']]);

            return [
                'user' => $user,
                'tokens' => $tokens
            ];

        } catch (Exception $e) {
            $this->logger->auth('login', $email, false, ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout(int $userId): bool
    {
        try {
            // Limpiar remember token
            $this->userRepository->clearRememberToken($userId);
            
            // Aquí podrías agregar el token a una blacklist en Redis
            
            $this->logger->auth('logout', 'user_' . $userId, true);
            return true;

        } catch (Exception $e) {
            $this->logger->auth('logout', 'user_' . $userId, false, [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Refrescar token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            // Decodificar refresh token
            $payload = $this->decodeToken($refreshToken);

            // Verificar que sea un refresh token
            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                throw new Exception('Token inválido');
            }

            // Buscar usuario
            $user = $this->userRepository->find($payload['user_id']);

            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }

            if (!$user['is_active']) {
                throw new Exception('Usuario inactivo');
            }

            // Generar nuevos tokens
            $tokens = $this->generateTokens($user);

            $this->logger->auth('refresh_token', 'user_' . $user['id'], true);

            return [
                'user' => $user,
                'tokens' => $tokens
            ];

        } catch (Exception $e) {
            $this->logger->auth('refresh_token', 'unknown', false, [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener usuario actual por token
     */
    public function getCurrentUser(string $token): ?array
    {
        try {
            $payload = $this->decodeToken($token);
            
            if (!isset($payload['user_id'])) {
                return null;
            }

            return $this->userRepository->find($payload['user_id']);

        } catch (Exception $e) {
            $this->logger->warning('Failed to get current user', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generar access token y refresh token
     */
    private function generateTokens(array $user): array
    {
        $now = time();
        
        // Access Token
        $accessTokenPayload = [
            'iss' => $this->jwtConfig['issuer'],
            'aud' => $this->jwtConfig['audience'],
            'iat' => $now,
            'nbf' => $now + $this->jwtConfig['not_before'],
            'exp' => $now + $this->jwtConfig['access_token_expiration'],
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'type' => 'access'
        ];

        $accessToken = JWT::encode(
            $accessTokenPayload,
            $this->jwtConfig['secret'],
            $this->jwtConfig['algorithm']
        );

        // Refresh Token
        $refreshTokenPayload = [
            'iss' => $this->jwtConfig['issuer'],
            'aud' => $this->jwtConfig['audience'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->jwtConfig['refresh_token_expiration'],
            'user_id' => $user['id'],
            'type' => 'refresh'
        ];

        $refreshToken = JWT::encode(
            $refreshTokenPayload,
            $this->jwtConfig['secret'],
            $this->jwtConfig['algorithm']
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtConfig['access_token_expiration']
        ];
    }

    /**
     * Decodificar y validar token
     */
    public function decodeToken(string $token): object
    {
        try {
            return JWT::decode(
                $token,
                new Key($this->jwtConfig['secret'], $this->jwtConfig['algorithm'])
            );
        } catch (Exception $e) {
            throw new Exception('Token inválido o expirado');
        }
    }

    /**
     * Validar token
     */
    public function validateToken(string $token): bool
    {
        try {
            $this->decodeToken($token);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            // Buscar usuario con password
            $user = $this->userRepository->find($userId);
            
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }

            // Buscar con password para verificar
            $userWithPassword = $this->userRepository->findByEmailWithPassword($user['email']);

            // Verificar contraseña actual
            if (!$this->userRepository->verifyPassword($currentPassword, $userWithPassword['password'])) {
                throw new Exception('Contraseña actual incorrecta');
            }

            // Actualizar contraseña
            return $this->userRepository->updatePassword($userId, $newPassword);

        } catch (Exception $e) {
            $this->logger->error('Failed to change password', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Solicitar reseteo de contraseña
     */
    public function requestPasswordReset(string $email): bool
    {
        try {
            $user = $this->userRepository->findByEmail($email);

            if (!$user) {
                // No revelar si el email existe o no por seguridad
                return true;
            }

            // Generar token de reseteo
            $resetToken = bin2hex(random_bytes(32));
            
            // Guardar token en BD (necesitarías crear una tabla password_resets)
            // Por ahora solo logueamos
            $this->logger->info('Password reset requested', [
                'email' => $email,
                'token' => $resetToken
            ]);

            // Aquí enviarías el email con el token
            
            return true;

        } catch (Exception $e) {
            $this->logger->error('Failed to request password reset', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}