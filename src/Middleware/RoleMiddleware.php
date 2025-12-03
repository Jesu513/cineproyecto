<?php
// ============================================
// RoleMiddleware
// Verifica que el usuario tenga el rol requerido
// ============================================

namespace App\Middleware;

use App\Services\AuthService;
use App\Utils\Response;

class RoleMiddleware
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * @param array|string $roles
     */
    public function handle($roles)
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return Response::json([
                "success" => false,
                "message" => "No autenticado"
            ], 401);
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        if (!in_array($user['role'], $roles)) {
            return Response::json([
                "success" => false,
                "message" => "Acceso denegado. Rol no autorizado."
            ], 403);
        }

        return true; // acceso permitido
    }
}
