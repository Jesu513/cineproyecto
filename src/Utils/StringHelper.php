<?php
// ============================================
// StringHelper
// Funciones comunes para manejo de texto
// ============================================

namespace App\Utils;

class StringHelper
{
    public static function random(int $length = 16): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    public static function bookingCode(): string
    {
        return strtoupper(substr(md5(uniqid('', true)), 0, 10));
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public static function sanitize(string $text): string
    {
        return htmlspecialchars(strip_tags($text), ENT_QUOTES, 'UTF-8');
    }

    public static function shorten(string $text, int $max = 50): string
    {
        return strlen($text) > $max 
            ? substr($text, 0, $max) . "..." 
            : $text;
    }
}
