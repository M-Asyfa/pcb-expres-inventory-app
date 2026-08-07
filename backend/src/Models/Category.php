<?php
namespace App\Models;

use App\Config\Database;

/**
 * Model for `kategori` table
 * Primary key = kategori varchar
 * Also provides aggregated stats from data_barang
 */
class Category {
    public static function cleanEmpty(): int {
        $pdo = Database::getConnection();
        try {
            // Remove empty kategori entries and move their products to Lain-lain
            $pdo->exec("INSERT IGNORE INTO kategori (kategori) VALUES ('Lain-lain')");
            $upd = $pdo->exec("UPDATE data_barang SET kategori='Lain-lain' WHERE kategori IS NULL OR TRIM(kategori)=''");
            $del = $pdo->exec("DELETE FROM kategori WHERE TRIM(kategori)='' OR kategori IS NULL");
            return (int)($del ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function all(): array {
        $pdo = Database::getConnection();
        // Auto clean empty on read to prevent (empty) row with 0 counts (Screenshot Capture2)
        self::cleanEmpty();
        $sql = "
            SELECT k.kategori as name, k.kategori as id, k.kategori as kategori,
                   COUNT(d.id) as product_count,
                   COALESCE(SUM(d.stock),0) as total_stock,
                   COALESCE(SUM(d.stock * d.harga),0) as total_value
            FROM kategori k
            LEFT JOIN data_barang d ON d.kategori = k.kategori
            WHERE TRIM(k.kategori) != '' AND k.kategori IS NOT NULL
            GROUP BY k.kategori
            ORDER BY k.kategori ASC
        ";
        try {
            $data = $pdo->query($sql)->fetchAll();
            $orphans = $pdo->query("
                SELECT d.kategori as name, d.kategori as id, d.kategori as kategori,
                       COUNT(*) as product_count,
                       SUM(d.stock) as total_stock,
                       SUM(d.stock * d.harga) as total_value
                FROM data_barang d
                LEFT JOIN kategori k ON k.kategori = d.kategori
                WHERE k.kategori IS NULL AND d.kategori IS NOT NULL AND TRIM(d.kategori) != ''
                GROUP BY d.kategori
            ")->fetchAll();
            return array_merge($data, $orphans);
        } catch (\Exception $e) {
            return $pdo->query("SELECT kategori as name, kategori as id, kategori, COUNT(*) as product_count, SUM(stock) as total_stock, SUM(stock*harga) as total_value FROM data_barang WHERE kategori IS NOT NULL AND TRIM(kategori) != '' GROUP BY kategori ORDER BY kategori")->fetchAll();
        }
    }

    public static function find(string $id): ?array {
        $pdo = Database::getConnection();
        // $id is kategori name
        $stmt = $pdo->prepare("SELECT kategori as name, kategori as id, kategori FROM kategori WHERE kategori = :k");
        $stmt->execute(['k' => $id]);
        $result = $stmt->fetch();
        if (!$result) {
            // try find in data_barang as fallback
            $stmt2 = $pdo->prepare("SELECT kategori as name, kategori as id, kategori FROM data_barang WHERE kategori = :k LIMIT 1");
            $stmt2->execute(['k' => $id]);
            $result = $stmt2->fetch() ?: null;
        }
        if ($result) {
            $count = $pdo->prepare("SELECT COUNT(*) as c, SUM(stock) as s FROM data_barang WHERE kategori = :k");
            $count->execute(['k' => $id]);
            $stats = $count->fetch();
            $result['product_count'] = $stats['c'];
            $result['total_stock'] = $stats['s'];
        }
        return $result ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $name = $data['name'] ?? $data['kategori'] ?? null;
        if (!$name) throw new \Exception("Name required");
        $name = trim((string)$name);
        if ($name === '') throw new \Exception("Name cannot be empty");
        $stmt = $pdo->prepare("INSERT IGNORE INTO kategori (kategori) VALUES (:k)");
        $stmt->execute(['k' => $name]);
        return 1;
    }

    public static function update(string $id, array $data): bool {
        $pdo = Database::getConnection();
        $newName = $data['name'] ?? $data['kategori'] ?? null;
        if (!$newName) return false;
        if ($newName === $id) return true;

        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT IGNORE INTO kategori (kategori) VALUES (:k)")->execute(['k'=>$newName]);
            $pdo->prepare("UPDATE data_barang SET kategori = :new WHERE kategori = :old")->execute(['new'=>$newName,'old'=>$id]);
            $pdo->prepare("DELETE FROM kategori WHERE kategori = :old")->execute(['old'=>$id]);
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function delete(string $id): bool {
        $id = trim($id);
        if ($id === '') throw new \Exception('Invalid kategori id');
        if (strcasecmp($id, 'Lain-lain') === 0) throw new \Exception('Tidak boleh hapus kategori Lain-lain');
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            // Ensure Lain-lain exists
            $pdo->prepare("INSERT IGNORE INTO kategori (kategori) VALUES ('Lain-lain')")->execute();
            // Move all barang in deleted kategori to Lain-lain (as requested)
            try {
                $pdo->prepare("UPDATE data_barang SET kategori = 'Lain-lain' WHERE TRIM(kategori) = :k")->execute(['k'=>$id]);
            } catch (\Exception $e) {
                $pdo->prepare("UPDATE data_barang SET kategori = 'Lain-lain' WHERE kategori = :k")->execute(['k'=>$id]);
            }
            $stmt = $pdo->prepare("DELETE FROM kategori WHERE kategori = :k");
            $stmt->execute(['k'=>$id]);
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Stats per category - fixed to include empty categories from kategori table, but exclude blank
    public static function stats(): array {
        $pdo = Database::getConnection();
        self::cleanEmpty();
        try {
            $sql = "
                SELECT k.kategori,
                       COUNT(d.id) as product_count,
                       COALESCE(SUM(d.stock),0) as total_stock,
                       COALESCE(SUM(d.stock * d.harga),0) as total_value
                FROM kategori k
                LEFT JOIN data_barang d ON d.kategori = k.kategori
                WHERE TRIM(k.kategori) != '' AND k.kategori IS NOT NULL
                GROUP BY k.kategori
                UNION ALL
                SELECT d.kategori as kategori,
                       COUNT(*) as product_count,
                       SUM(d.stock) as total_stock,
                       SUM(d.stock * d.harga) as total_value
                FROM data_barang d
                LEFT JOIN kategori k ON k.kategori = d.kategori
                WHERE k.kategori IS NULL AND d.kategori IS NOT NULL AND TRIM(d.kategori) != ''
                GROUP BY d.kategori
                ORDER BY product_count DESC, kategori ASC
            ";
            return $pdo->query($sql)->fetchAll();
        } catch (\Exception $e) {
            return $pdo->query("SELECT kategori, COUNT(*) as product_count, SUM(stock) as total_stock, SUM(stock*harga) as total_value FROM data_barang WHERE kategori IS NOT NULL AND TRIM(kategori) != '' GROUP BY kategori ORDER BY product_count DESC")->fetchAll();
        }
    }
}
