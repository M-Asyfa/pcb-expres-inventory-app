<?php
namespace App\Middleware;

use App\Core\Response;

class Auth {
    private static function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        return $default;
    }

    /**
     * Simple Bearer token protection for mutating methods.
     * If API_TOKEN env is empty, auth is disabled (dev mode).
     * GET and health check remain open.
     */
    public static function handle(): void {
        $token = self::env('API_TOKEN', null);
        // Allow disabling auth if no token configured
        if (!$token || trim($token) === '') {
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
        $uri = str_replace('/backend/public', '', $uri);

        // Public endpoints that never require auth
        $publicPaths = [
            '/api/health',
        ];
        foreach ($publicPaths as $p) {
            if (strpos($uri, $p) === 0) {
                return;
            }
        }

        // Only protect mutating methods by default
        // If you want to protect all, set API_PROTECT_ALL=true
        $protectAll = self::env('API_PROTECT_ALL', 'false');
        $shouldProtect = in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true);
        if (strtolower($protectAll) === 'true') {
            $shouldProtect = true;
            // Still allow OPTIONS (handled by CORS)
            if ($method === 'OPTIONS') $shouldProtect = false;
        }

        if (!$shouldProtect) {
            return;
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (!str_starts_with($authHeader, 'Bearer ')) {
            Response::error('Unauthorized: Missing Bearer token', 401);
        }

        $provided = trim(substr($authHeader, 7));
        // Use hash_equals to prevent timing attacks
        if (!hash_equals($token, $provided)) {
            Response::error('Unauthorized: Invalid token', 401);
        }
    }
}
