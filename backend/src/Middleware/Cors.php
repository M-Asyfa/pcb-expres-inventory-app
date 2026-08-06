<?php
namespace App\Middleware;

class Cors {
    public static function handle(): void {
        $allowedOriginsRaw = $_ENV['CORS_ALLOWED_ORIGINS'] ?? $_SERVER['CORS_ALLOWED_ORIGINS'] ?? getenv('CORS_ALLOWED_ORIGINS') ?: '';
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $isWildcard = $allowedOriginsRaw === '*' || trim($allowedOriginsRaw) === '';
        $allowedOrigins = $isWildcard ? [] : array_map('trim', explode(',', $allowedOriginsRaw));
        $allowedOrigins = array_filter($allowedOrigins, fn($o) => $o !== '');

        $originAllowed = false;
        if ($isWildcard) {
            // Dev mode: allow all, but never with credentials
            header("Access-Control-Allow-Origin: *");
            $originAllowed = true;
        } elseif ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: $requestOrigin");
            header("Access-Control-Allow-Credentials: true");
            header("Vary: Origin");
            $originAllowed = true;
        } elseif ($requestOrigin === '') {
            // No Origin header (curl, Postman, same-origin) – allow first configured origin for non-browser,
            // or allow without CORS header block for server-to-server
            // Do NOT set Access-Control-Allow-Origin to avoid leaking
            $originAllowed = true;
        } else {
            // Origin not allowed – do not set Allow-Origin header, browser will block
            $originAllowed = false;
            // For preflight from disallowed origin, respond 403
            if ($requestMethod === 'OPTIONS') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CORS origin not allowed']);
                exit;
            }
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, Accept, Origin");
        header("Access-Control-Max-Age: 86400");

        // Handle preflight OPTIONS request immediately
        if ($requestMethod === 'OPTIONS') {
            if (!$originAllowed && $requestOrigin !== '') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CORS origin not allowed']);
                exit;
            }
            http_response_code(200);
            exit;
        }
    }
}
