<?php
namespace App\Middleware;

class Cors {
    public static function handle(): void {
        // For development: allow all origins for 5173 frontend
        // In production, set CORS_ALLOWED_ORIGINS to specific domains
        $allowedOrigins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? $_SERVER['CORS_ALLOWED_ORIGINS'] ?? getenv('CORS_ALLOWED_ORIGINS') ?: '*';
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Always allow localhost:5173 and localhost:3000 for dev
        if ($allowedOrigins === '*' || $allowedOrigins === '') {
            header("Access-Control-Allow-Origin: *");
        } else {
            $origins = array_map('trim', explode(',', $allowedOrigins));
            // If request origin is in allowed list, reflect it back (required for credentials)
            if ($requestOrigin && in_array($requestOrigin, $origins)) {
                header("Access-Control-Allow-Origin: $requestOrigin");
                header("Access-Control-Allow-Credentials: true");
                header("Vary: Origin");
            } else {
                // Fallback: allow first origin or * for dev
                // For preflight, allow requesting origin to avoid CORS block
                if ($requestOrigin) {
                    header("Access-Control-Allow-Origin: $requestOrigin");
                    header("Vary: Origin");
                } else {
                    header("Access-Control-Allow-Origin: " . ($origins[0] ?? "*"));
                }
            }
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, Accept, Origin");
        header("Access-Control-Max-Age: 86400");

        // Handle preflight OPTIONS request immediately
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(200);
            // Ensure no further output
            exit;
        }
    }
}
