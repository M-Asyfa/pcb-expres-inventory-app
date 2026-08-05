<?php
// Try composer autoload if exists, otherwise use built-in fallback
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    // Load env via vlucas/phpdotenv if available
    if (class_exists('Dotenv\Dotenv')) {
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        } elseif (file_exists(__DIR__ . '/../.env.example')) {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.example');
            $dotenv->load();
        }
    }
} else {
    // Fallback PSR-4 autoloader without composer
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base_dir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) require $file;
    });
    // Simple .env loader (no external lib) - respects Docker env vars
    $envFile = file_exists(__DIR__ . '/../.env') ? __DIR__ . '/../.env' : (__DIR__ . '/../.env.example');
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (!str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            $val = trim($val, "\"'");
            // Don't overwrite if already set by Docker env
            if (getenv($key) !== false || isset($_ENV[$key]) || isset($_SERVER[$key])) {
                // Keep Docker's value (e.g. DB_HOST=db), but ensure both superglobals have it
                if (!isset($_ENV[$key]) && isset($_SERVER[$key])) $_ENV[$key] = $_SERVER[$key];
                if (!isset($_SERVER[$key]) && isset($_ENV[$key])) $_SERVER[$key] = $_ENV[$key];
                continue;
            }
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
            putenv("$key=$val");
        }
    }
    // Ensure Docker env vars are also in $_ENV (phpdotenv quirk)
    foreach (['DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD','APP_DEBUG'] as $k) {
        if (getenv($k) !== false && !isset($_ENV[$k])) $_ENV[$k] = getenv($k);
        if (isset($_SERVER[$k]) && !isset($_ENV[$k])) $_ENV[$k] = $_SERVER[$k];
    }
}

use App\Middleware\Cors;

// CORS
Cors::handle();

// Error handling
error_reporting(E_ALL);
$debug = ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? 'true') === 'true' || ($_ENV['APP_DEBUG'] ?? true) === true;
ini_set('display_errors', $debug ? '1' : '0');

// Load routes and dispatch
$router = require __DIR__ . '/../src/Routes/api.php';
$router->dispatch();
