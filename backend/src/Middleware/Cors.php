<?php
namespace App\Middleware;

class Cors {
    public static function handle(): void {
        $allowedOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*';
        
        // If multiple origins configured, check request origin
        if ($allowedOrigins !== '*') {
            $origins = array_map('trim', explode(',', $allowedOrigins));
            $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
            if (in_array($requestOrigin, $origins)) {
                header("Access-Control-Allow-Origin: $requestOrigin");
            } else if (!empty($origins)) {
                header("Access-Control-Allow-Origin: " . $origins[0]);
            }
        } else {
            header("Access-Control-Allow-Origin: *");
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
    }
}
