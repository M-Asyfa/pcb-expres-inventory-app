<?php
namespace App\Middleware;

class Cors {
    private static function env(string $key, $default = null) {
        $v = getenv($key);
        if ($v !== false && $v !== '') return $v;
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        return $default;
    }

    private static function isPrivateOrigin(string $origin): bool {
        if ($origin === '') return false;
        // Allow localhost and private ranges without explicit config for LAN usage
        $patterns = [
            '#^https?://localhost(:\d+)?$#',
            '#^https?://127\.0\.0\.1(:\d+)?$#',
            '#^https?://192\.168\.\d+\.\d+(:\d+)?$#',
            '#^https?://10\.\d+\.\d+\.\d+(:\d+)?$#',
            '#^https?://172\.(1[6-9]|2\d|3[01])\.\d+\.\d+(:\d+)?$#',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $origin)) return true;
        }
        return false;
    }

    public static function handle(): void {
        $allowedOriginsRaw = self::env('CORS_ALLOWED_ORIGINS', '');
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $debugRaw = self::env('APP_DEBUG', 'false');
        $isDebug = $debugRaw === 'true' || $debugRaw === true || $debugRaw === '1';

        $isWildcard = $allowedOriginsRaw === '*' || trim($allowedOriginsRaw) === '';
        $allowedOrigins = $isWildcard ? [] : array_map('trim', explode(',', $allowedOriginsRaw));
        $allowedOrigins = array_filter($allowedOrigins, fn($o) => $o !== '');

        $originAllowed = false;

        // Debug mode: allow any origin (reflect) – needed for LAN development
        if ($isDebug && $requestOrigin !== '') {
            header("Access-Control-Allow-Origin: $requestOrigin");
            header("Access-Control-Allow-Credentials: true");
            header("Vary: Origin");
            $originAllowed = true;
        } elseif ($isWildcard) {
            // If wildcard explicitly set or empty, reflect origin if present (allows Authorization), otherwise *
            if ($requestOrigin !== '') {
                header("Access-Control-Allow-Origin: $requestOrigin");
                header("Access-Control-Allow-Credentials: true");
                header("Vary: Origin");
            } else {
                header("Access-Control-Allow-Origin: *");
            }
            $originAllowed = true;
        } elseif ($requestOrigin !== '' && in_array($requestOrigin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: $requestOrigin");
            header("Access-Control-Allow-Credentials: true");
            header("Vary: Origin");
            $originAllowed = true;
        } elseif ($requestOrigin !== '' && self::isPrivateOrigin($requestOrigin)) {
            // Auto-allow LAN / localhost even if not in explicit whitelist – fixes other PC in same network
            header("Access-Control-Allow-Origin: $requestOrigin");
            header("Access-Control-Allow-Credentials: true");
            header("Vary: Origin");
            $originAllowed = true;
        } elseif ($requestOrigin === '') {
            $originAllowed = true;
        } else {
            $originAllowed = false;
            if ($requestMethod === 'OPTIONS') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'CORS origin not allowed: ' . $requestOrigin]);
                exit;
            }
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, Accept, Origin");
        header("Access-Control-Max-Age: 86400");

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
