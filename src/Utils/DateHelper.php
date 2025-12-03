<?php
// ============================================
// DateHelper
// Funciones útiles para manejo de fechas
// ============================================

namespace App\Utils;

class DateHelper
{
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    public static function today(): string
    {
        return date('Y-m-d');
    }

    public static function addMinutes(int $minutes): string
    {
        return date('Y-m-d H:i:s', time() + ($minutes * 60));
    }

    public static function isPast(string $date): bool
    {
        return strtotime($date) < time();
    }

    public static function formatDate(string $date): string
    {
        return date('d/m/Y', strtotime($date));
    }

    public static function formatDateTime(string $datetime): string
    {
        return date('d/m/Y H:i', strtotime($datetime));
    }
}
