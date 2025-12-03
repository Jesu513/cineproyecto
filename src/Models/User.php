<?php
// ============================================
// src/Models/User.php
// Modelo de usuario
// ============================================

namespace App\Models;

class User extends BaseModel
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'birth_date',
        'avatar_url',
        'is_active',
        'email_verified_at'
    ];

    protected array $hidden = [
        'password',
        'remember_token'
    ];

    protected array $casts = [
        'id' => 'int',
        'is_active' => 'bool',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Crear usuario con contraseña hasheada
     */
    public function createUser(array $data): array
    {
        // Hashear contraseña
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        // Role por defecto
        if (!isset($data['role'])) {
            $data['role'] = 'customer';
        }

        // Activo por defecto
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $this->create($data);
    }

    /**
     * Buscar por email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Verificar contraseña
     */
    public function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Buscar usuario con contraseña (para autenticación)
     */
    public function findByEmailWithPassword(string $email): ?array
    {
        $result = $this->queryBuilder
            ->table($this->table)
            ->where('email', $email)
            ->first();

        return $result;
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $userId)
            ->update([
                'password' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Verificar si email existe
     */
    public function emailExists(string $email): bool
    {
        return $this->queryBuilder
            ->table($this->table)
            ->where('email', $email)
            ->exists();
    }

    /**
     * Activar/desactivar usuario
     */
    public function toggleActive(int $userId, bool $isActive): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $userId)
            ->update([
                'is_active' => $isActive,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Verificar email
     */
    public function verifyEmail(int $userId): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $userId)
            ->update([
                'email_verified_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Obtener usuarios por rol
     */
    public function getByRole(string $role): array
    {
        return $this->findAllBy('role', $role);
    }

    /**
     * Actualizar remember token
     */
    public function updateRememberToken(int $userId, string $token): bool
    {
        $affected = $this->queryBuilder
            ->table($this->table)
            ->where($this->primaryKey, $userId)
            ->update([
                'remember_token' => $token,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        return $affected > 0;
    }

    /**
     * Limpiar remember token
     */
    public function clearRememberToken(int $userId): bool
    {
        return $this->updateRememberToken($userId, '');
    }

    /**
     * Buscar por remember token
     */
    public function findByRememberToken(string $token): ?array
    {
        $result = $this->queryBuilder
            ->table($this->table)
            ->where('remember_token', $token)
            ->first();

        return $result ? $this->hideAttributes($result) : null;
    }

    /**
     * Obtener estadísticas de usuarios
     */
    public function getStats(): array
    {
        $total = $this->count();
        
        $byRole = $this->queryBuilder
            ->table($this->table)
            ->select(['role', 'COUNT(*) as count'])
            ->groupBy('role')
            ->get();

        $active = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'by_role' => $byRole
        ];
    }

    /**
     * Buscar usuarios activos
     */
    public function getActiveUsers(int $limit = 100): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->where('is_active', true)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }

    /**
     * Buscar usuarios recientes
     */
    public function getRecentUsers(int $limit = 10): array
    {
        $results = $this->queryBuilder
            ->table($this->table)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        return array_map(fn($item) => $this->hideAttributes($item), $results);
    }
}