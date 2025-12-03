<?php
// ============================================
// src/Repositories/UserRepository.php
// Repositorio de usuarios
// ============================================

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    protected User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
        parent::__construct($this->userModel);
    }

    /**
     * Crear usuario con contraseña hasheada
     */
    public function createUser(array $data): array
    {
        try {
            $this->logger->info('Creating new user', ['email' => $data['email'] ?? null]);
            return $this->userModel->createUser($data);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['email' => $data['email'] ?? null]);
            throw $e;
        }
    }

    /**
     * Buscar por email
     */
    public function findByEmail(string $email): ?array
    {
        try {
            return $this->userModel->findByEmail($email);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['email' => $email]);
            throw $e;
        }
    }

    /**
     * Buscar por email con contraseña (para login)
     */
    public function findByEmailWithPassword(string $email): ?array
    {
        try {
            return $this->userModel->findByEmailWithPassword($email);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['email' => $email]);
            throw $e;
        }
    }

    /**
     * Verificar contraseña
     */
    public function verifyPassword(string $password, string $hashedPassword): bool
    {
        return $this->userModel->verifyPassword($password, $hashedPassword);
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            $this->logger->info('Updating password', ['user_id' => $userId]);
            return $this->userModel->updatePassword($userId, $newPassword);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['user_id' => $userId]);
            throw $e;
        }
    }

    /**
     * Verificar si email existe
     */
    public function emailExists(string $email): bool
    {
        try {
            return $this->userModel->emailExists($email);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['email' => $email]);
            throw $e;
        }
    }

    /**
     * Activar/desactivar usuario
     */
    public function toggleActive(int $userId, bool $isActive): bool
    {
        try {
            $this->logger->info('Toggle user active status', [
                'user_id' => $userId,
                'is_active' => $isActive
            ]);
            return $this->userModel->toggleActive($userId, $isActive);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['user_id' => $userId]);
            throw $e;
        }
    }

    /**
     * Verificar email
     */
    public function verifyEmail(int $userId): bool
    {
        try {
            $this->logger->info('Verifying email', ['user_id' => $userId]);
            return $this->userModel->verifyEmail($userId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['user_id' => $userId]);
            throw $e;
        }
    }

    /**
     * Obtener usuarios por rol
     */
    public function getByRole(string $role): array
    {
        try {
            return $this->userModel->getByRole($role);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['role' => $role]);
            throw $e;
        }
    }

    /**
     * Actualizar remember token
     */
    public function updateRememberToken(int $userId, string $token): bool
    {
        try {
            return $this->userModel->updateRememberToken($userId, $token);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['user_id' => $userId]);
            throw $e;
        }
    }

    /**
     * Limpiar remember token
     */
    public function clearRememberToken(int $userId): bool
    {
        try {
            return $this->userModel->clearRememberToken($userId);
        } catch (\Exception $e) {
            $this->logger->exception($e, ['user_id' => $userId]);
            throw $e;
        }
    }

    /**
     * Buscar por remember token
     */
    public function findByRememberToken(string $token): ?array
    {
        try {
            return $this->userModel->findByRememberToken($token);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener estadísticas
     */
    public function getStats(): array
    {
        try {
            return $this->userModel->getStats();
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener usuarios activos
     */
    public function getActiveUsers(int $limit = 100): array
    {
        try {
            return $this->userModel->getActiveUsers($limit);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }

    /**
     * Obtener usuarios recientes
     */
    public function getRecentUsers(int $limit = 10): array
    {
        try {
            return $this->userModel->getRecentUsers($limit);
        } catch (\Exception $e) {
            $this->logger->exception($e);
            throw $e;
        }
    }
}