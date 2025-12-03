<?php
// ============================================
// UserController
// Manejo de usuarios (perfil)
// ============================================

namespace App\Controllers;

use App\Services\AuthService;
use App\Repositories\UserRepository;

class UserController extends BaseController
{
    private AuthService $authService;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userRepo = new UserRepository();
    }

    /**
     * GET /api/users/me
     * Obtener perfil del usuario autenticado
     */
    public function me()
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return $this->error("No autenticado", 401);
        }

        return $this->success($user, "Perfil cargado");
    }

    /**
     * PUT /api/users/me
     * Actualizar perfil del usuario
     */
    public function updateProfile()
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return $this->error("No autenticado", 401);
        }

        $data = json_decode(file_get_contents("php://input"), true);

        $validation = $this->validate($data, [
            'name'  => 'nullable|min:3',
            'phone' => 'nullable',
            'birth_date' => 'nullable|date'
        ]);

        if (!$validation['valid']) {
            return $this->error("Datos inválidos", 422, $validation['errors']);
        }

        $updated = $this->userRepo->update($user['id'], $data);

        return $this->success($updated, "Perfil actualizado");
    }

    /**
     * PUT /api/users/change-password
     */
    public function changePassword()
    {
        $user = $this->authService->getAuthenticatedUser();

        if (!$user) {
            return $this->error("No autenticado", 401);
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['current']) || !isset($data['new'])) {
            return $this->error("Parámetros faltantes", 400);
        }

        $result = $this->authService->changePassword(
            $user['id'],
            $data['current'],
            $data['new']
        );

        if (!$result['success']) {
            return $this->error($result['message'], 400);
        }

        return $this->success([], "Contraseña actualizada");
    }
}
