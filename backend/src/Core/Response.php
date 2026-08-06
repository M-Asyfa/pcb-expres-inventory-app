<?php
namespace App\Core;

class Response {
    private static function isDebug(): bool {
        $raw = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: 'false';
        return $raw === 'true' || $raw === true || $raw === '1';
    }

    public static function json($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        $flags = JSON_UNESCAPED_UNICODE;
        if (self::isDebug()) $flags |= JSON_PRETTY_PRINT;
        echo json_encode($data, $flags);
        exit;
    }

    public static function error(string $message, int $status = 400, $details = null): void {
        // In production, hide internal 500 details
        if ($status >= 500 && !self::isDebug()) {
            $message = 'Internal server error';
            $details = null;
        }
        $payload = ['error' => $message];
        if ($details && self::isDebug()) $payload['details'] = $details;
        self::json($payload, $status);
    }
}
