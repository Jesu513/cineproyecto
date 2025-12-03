<?php
// ============================================
// ValidationMiddleware
// Valida entrada JSON según reglas definidas
// ============================================

namespace App\Middleware;

use App\Utils\Validator;
use App\Utils\Response;

class ValidationMiddleware
{
    public function handle(array $rules)
    {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!is_array($input)) {
            return Response::json([
                "success" => false,
                "message" => "JSON inválido"
            ], 422);
        }

        $validator = new Validator($input, $rules);

        if (!$validator->passes()) {
            return Response::json([
                "success" => false,
                "message" => "Validación fallida",
                "errors"  => $validator->errors()
            ], 422);
        }

        return true;
    }
}
