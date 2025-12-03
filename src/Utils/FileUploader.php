<?php
// ============================================
// FileUploader.php
// Manejo seguro de subida de archivos
// ============================================

namespace App\Utils;

class FileUploader
{
    private string $uploadDir;
    private array $allowedExtensions;
    private int $maxSize;

    public function __construct(
        string $uploadDir = __DIR__ . '/../../public/uploads/',
        array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'],
        int $maxSize = 5_000_000 // 5MB
    ) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->allowedExtensions = $allowedExtensions;
        $this->maxSize = $maxSize;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function upload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Error al subir archivo");
        }

        if ($file['size'] > $this->maxSize) {
            throw new \Exception("Archivo excede el tamaño máximo permitido");
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions)) {
            throw new \Exception("Extensión no permitida: $extension");
        }

        $newName = uniqid('file_', true) . '.' . $extension;
        $destination = $this->uploadDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \Exception("No se pudo guardar el archivo");
        }

        return [
            'filename' => $newName,
            'path'     => $destination,
            'url'      => "/uploads/" . $newName
        ];
    }

    public function delete(string $filename): bool
    {
        $file = $this->uploadDir . $filename;
        return file_exists($file) ? unlink($file) : false;
    }
}
