<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;

    private static function env(string $key, $default = null) {
        // Docker env vars take precedence over .env file
        $val = getenv($key);
        if ($val !== false && $val !== '') return $val;
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return $_ENV[$key];
        return $default;
    }

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = self::env('DB_HOST', '127.0.0.1');
            $port = self::env('DB_PORT', '3306');
            $db   = self::env('DB_DATABASE', 'inventory_pcbexpressjogja');
            $user = self::env('DB_USERNAME', 'inventory');
            $pass = self::env('DB_PASSWORD', 'inventory_secret');
            $charset = self::env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $attempts = [
                [$user, $pass],
                // Fallbacks for old installations that used root/root
                ['root', 'root'],
                ['root', self::env('MARIADB_ROOT_PASSWORD', 'root')],
                ['inventory', 'inventory_secret'],
            ];
            $lastError = null;
            foreach ($attempts as [$u,$p]) {
                try {
                    $dsnTry = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
                    self::$instance = new PDO($dsnTry, $u, $p, $options);
                    // Auto-create minimal schema if tables missing (fresh DB without init scripts)
                    try {
                        $tables = self::$instance->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                        $hasDataBarang = false;
                        foreach ($tables as $t) { if (strtolower($t) === 'data_barang') { $hasDataBarang = true; break; } }
                        if (!$hasDataBarang) {
                            self::$instance->exec("
                                CREATE TABLE IF NOT EXISTS data_barang (
                                  id INT(11) NOT NULL AUTO_INCREMENT,
                                  updated TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                                  nama TEXT DEFAULT NULL,
                                  kategori TEXT DEFAULT NULL,
                                  keterangan_barang TEXT DEFAULT NULL,
                                  nomor_box VARCHAR(200) NOT NULL DEFAULT '0',
                                  nomor_laci VARCHAR(200) NOT NULL DEFAULT '1',
                                  harga INT(11) NOT NULL DEFAULT 0,
                                  stock INT(11) NOT NULL DEFAULT 0,
                                  batas_stock INT(11) NOT NULL DEFAULT 10,
                                  foto VARCHAR(500) DEFAULT NULL,
                                  PRIMARY KEY (id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                                CREATE TABLE IF NOT EXISTS kategori (kategori VARCHAR(200) NOT NULL, PRIMARY KEY (kategori)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                                CREATE TABLE IF NOT EXISTS log_stock (id INT(11) NOT NULL, waktu TIMESTAMP NOT NULL DEFAULT current_timestamp(), stock INT(11) NOT NULL, KEY idx_log_stock_id (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                            ");
                        } else {
                            // Ensure foto column
                            $check = self::$instance->query("SHOW COLUMNS FROM data_barang LIKE 'foto'");
                            if ($check && $check->fetch() === false) {
                                self::$instance->exec("ALTER TABLE data_barang ADD COLUMN IF NOT EXISTS foto VARCHAR(500) DEFAULT NULL");
                            }
                        }
                    } catch (\Exception $e) {
                        // Log but don't fail connection – table may still be usable
                        error_log('Auto-migration check failed: ' . $e->getMessage());
                    }
                    return self::$instance;
                } catch (PDOException $e) {
                    $lastError = $e;
                    continue;
                }
            }
            http_response_code(500);
            $debug = self::env('APP_DEBUG', 'false') === 'true';
            $msg = $debug ? ('Database connection failed: ' . $lastError->getMessage()) : 'Database connection failed';
            echo json_encode(['error' => $msg, 'host'=>$host, 'db'=>$db, 'user'=>$user]);
            exit;
        }
        return self::$instance;
    }
}
