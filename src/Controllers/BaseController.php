<?php
// ============================================
// BaseController
// Controlador padre con respuestas estandarizadas
// ============================================

namespace App\Controllers;

use App\Utils\Response;

class BaseController
{
    protected function success($data = [], string $message = 'OK', int $status = 200)
    {
        return Response::json([
            'success' => true,
            'message' => $message,
            'data'    => $data
        ], $status);
    }

    protected function error(string $message = 'Error', int $status = 400, $errors = [])
    {
        return Response::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors
        ], $status);
    }

    protected function validate(array $data, array $rules)
    {
        $validator = new \App\Utils\Validator($data, $rules);

        if (!$validator->passes()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'valid' => true,
            'errors' => []
        ];
    }
}
