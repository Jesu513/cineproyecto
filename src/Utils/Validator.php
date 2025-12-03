<?php
// ============================================
// src/Utils/Validator.php
// Clase para validación de datos
// ============================================

namespace App\Utils;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages = [];

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    /**
     * Validar datos según reglas
     */
    public function validate(): bool
    {
        foreach ($this->rules as $field => $rules) {
            $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Aplicar regla de validación
     */
    private function applyRule(string $field, string $rule): void
    {
        // Separar regla y parámetros (ejemplo: min:5)
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameters = $parts[1] ?? null;

        $value = $this->data[$field] ?? null;

        // Validaciones
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, 'required');
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                }
                break;

            case 'min':
                if ($value && strlen($value) < (int)$parameters) {
                    $this->addError($field, 'min', ['min' => $parameters]);
                }
                break;

            case 'max':
                if ($value && strlen($value) > (int)$parameters) {
                    $this->addError($field, 'max', ['max' => $parameters]);
                }
                break;

            case 'numeric':
                if ($value && !is_numeric($value)) {
                    $this->addError($field, 'numeric');
                }
                break;

            case 'integer':
                if ($value && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, 'integer');
                }
                break;

            case 'string':
                if ($value && !is_string($value)) {
                    $this->addError($field, 'string');
                }
                break;

            case 'boolean':
                if ($value !== null && !is_bool($value) && !in_array($value, [0, 1, '0', '1', true, false], true)) {
                    $this->addError($field, 'boolean');
                }
                break;

            case 'array':
                if ($value && !is_array($value)) {
                    $this->addError($field, 'array');
                }
                break;

            case 'date':
                if ($value && !strtotime($value)) {
                    $this->addError($field, 'date');
                }
                break;

            case 'alpha':
                if ($value && !ctype_alpha($value)) {
                    $this->addError($field, 'alpha');
                }
                break;

            case 'alphanumeric':
                if ($value && !ctype_alnum($value)) {
                    $this->addError($field, 'alphanumeric');
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'url');
                }
                break;

            case 'phone':
                if ($value && !preg_match('/^[0-9]{9,15}$/', $value)) {
                    $this->addError($field, 'phone');
                }
                break;

            case 'in':
                $allowedValues = explode(',', $parameters);
                if ($value && !in_array($value, $allowedValues)) {
                    $this->addError($field, 'in', ['values' => $parameters]);
                }
                break;

            case 'unique':
                // Formato: unique:table,column
                $params = explode(',', $parameters);
                $table = $params[0];
                $column = $params[1] ?? $field;
                
                if ($value && $this->existsInDatabase($table, $column, $value)) {
                    $this->addError($field, 'unique');
                }
                break;

            case 'exists':
                // Formato: exists:table,column
                $params = explode(',', $parameters);
                $table = $params[0];
                $column = $params[1] ?? 'id';
                
                if ($value && !$this->existsInDatabase($table, $column, $value)) {
                    $this->addError($field, 'exists');
                }
                break;

            case 'confirmed':
                // Verifica que exista campo_confirmation
                $confirmationField = $field . '_confirmation';
                if (!isset($this->data[$confirmationField]) || $value !== $this->data[$confirmationField]) {
                    $this->addError($field, 'confirmed');
                }
                break;

            case 'same':
                if (!isset($this->data[$parameters]) || $value !== $this->data[$parameters]) {
                    $this->addError($field, 'same', ['other' => $parameters]);
                }
                break;

            case 'different':
                if (isset($this->data[$parameters]) && $value === $this->data[$parameters]) {
                    $this->addError($field, 'different', ['other' => $parameters]);
                }
                break;

            case 'regex':
                if ($value && !preg_match($parameters, $value)) {
                    $this->addError($field, 'regex');
                }
                break;

            case 'json':
                if ($value) {
                    json_decode($value);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->addError($field, 'json');
                    }
                }
                break;

            case 'file':
                if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                    $this->addError($field, 'file');
                }
                break;

            case 'image':
                if (isset($_FILES[$field])) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $fileType = $_FILES[$field]['type'];
                    if (!in_array($fileType, $allowedTypes)) {
                        $this->addError($field, 'image');
                    }
                }
                break;

            case 'max_file_size':
                if (isset($_FILES[$field])) {
                    $maxSize = (int)$parameters * 1024; // KB to bytes
                    if ($_FILES[$field]['size'] > $maxSize) {
                        $this->addError($field, 'max_file_size', ['max' => $parameters]);
                    }
                }
                break;
        }
    }

    /**
     * Agregar error de validación
     */
    private function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->customMessages["{$field}.{$rule}"] 
            ?? $this->customMessages[$rule] 
            ?? $this->getDefaultMessage($field, $rule, $params);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Obtener mensaje de error por defecto
     */
    private function getDefaultMessage(string $field, string $rule, array $params = []): string
    {
        $fieldName = ucfirst(str_replace('_', ' ', $field));

        $messages = [
            'required' => "{$fieldName} es requerido.",
            'email' => "{$fieldName} debe ser un email válido.",
            'min' => "{$fieldName} debe tener al menos {$params['min']} caracteres.",
            'max' => "{$fieldName} no debe exceder {$params['max']} caracteres.",
            'numeric' => "{$fieldName} debe ser un número.",
            'integer' => "{$fieldName} debe ser un número entero.",
            'string' => "{$fieldName} debe ser una cadena de texto.",
            'boolean' => "{$fieldName} debe ser verdadero o falso.",
            'array' => "{$fieldName} debe ser un array.",
            'date' => "{$fieldName} debe ser una fecha válida.",
            'alpha' => "{$fieldName} solo debe contener letras.",
            'alphanumeric' => "{$fieldName} solo debe contener letras y números.",
            'url' => "{$fieldName} debe ser una URL válida.",
            'phone' => "{$fieldName} debe ser un número de teléfono válido.",
            'in' => "{$fieldName} debe ser uno de: {$params['values']}.",
            'unique' => "{$fieldName} ya existe en el sistema.",
            'exists' => "{$fieldName} no existe en el sistema.",
            'confirmed' => "{$fieldName} no coincide con la confirmación.",
            'same' => "{$fieldName} debe coincidir con {$params['other']}.",
            'different' => "{$fieldName} debe ser diferente de {$params['other']}.",
            'regex' => "{$fieldName} no tiene el formato correcto.",
            'json' => "{$fieldName} debe ser un JSON válido.",
            'file' => "{$fieldName} debe ser un archivo válido.",
            'image' => "{$fieldName} debe ser una imagen válida (jpg, png, gif, webp).",
            'max_file_size' => "{$fieldName} no debe exceder {$params['max']}KB.",
        ];

        return $messages[$rule] ?? "{$fieldName} es inválido.";
    }

    /**
     * Verificar si un valor existe en la base de datos
     */
    private function existsInDatabase(string $table, string $column, mixed $value): bool
    {
        try {
            $connection = \App\Database\Connection::getInstance();
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $result = $connection->fetchOne($sql, [$value]);
            return ($result['count'] ?? 0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener errores
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Verificar si hay errores
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
     * Verificar si la validación pasó
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * Obtener datos validados
     */
    public function validated(): array
    {
        if (!$this->validate()) {
            return [];
        }

        $validated = [];
        foreach (array_keys($this->rules) as $field) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    /**
     * Método estático para validar rápidamente
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Sanitizar datos de entrada
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Validar contraseña segura
     */
    public static function isStrongPassword(string $password): bool
    {
        $config = require __DIR__ . '/../../config/app.php';
        
        $minLength = $config['security']['password_min_length'];
        $requireUppercase = $config['security']['password_require_uppercase'];
        $requireLowercase = $config['security']['password_require_lowercase'];
        $requireNumbers = $config['security']['password_require_numbers'];
        $requireSpecialChars = $config['security']['password_require_special_chars'];

        if (strlen($password) < $minLength) {
            return false;
        }

        if ($requireUppercase && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($requireLowercase && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if ($requireNumbers && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if ($requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }
}