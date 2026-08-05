<?php
namespace App\Models;

use App\Config\Database;

/**
 * Model for `kategori` table
 * Primary key = kategori varchar
 * Also provides aggregated stats from data_barang
 */
class Category {
    public static function all(): array {
        $pdo = Database::getConnection();
        // Get categories from kategori table with counts, plus ensure we include orphan categories from data_barang
        $sql = "
            SELECT k.kategori as name, k.kategori as id, k.kategori as kategori,
                   COUNT(d.id) as product_count,
                   COALESCE(SUM(d.stock),0) as total_stock
            FROM kategori k
            LEFT JOIN data_barang d ON d.kategori = k.kategori
            GROUP BY k.kategori
            ORDER BY k.kategori ASC
        ";
        try {
            $data = $pdo->query($sql)->fetchAll();
            // Also check for categories in data_barang not in kategori table
            $orphans = $pdo->query("
                SELECT DISTINCT d.kategori as name, d.kategori as id, d.kategori as kategori,
                       COUNT(*) as product_count, SUM(stock) as total_stock
                FROM data_barang d
                LEFT JOIN kategori k ON k.kategori = d.kategori
                WHERE k.kategori IS NULL AND d.kategori IS NOT NULL AND d.kategori != ''
                GROUP BY d.kategori
            ")->fetchAll();
            return array_merge($data, $orphans);
        } catch (\Exception $e) {
            // Fallback: distinct categories from data_barang only
            return $pdo->query("SELECT kategori as name, kategori as id, kategori, COUNT(*) as product_count FROM data_barang GROUP BY kategori ORDER BY kategori")->fetchAll();
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
        $pdo = Database::getConnection();
        // Don't delete data_barang, just remove kategori entry and set orphan kategori to empty
        // Safer: only delete from kategori table, keep data_barang data
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE kategori = :k");
        return $stmt->execute(['k'=>$id]);
    }

    // Stats per category
    public static function stats(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT kategori, COUNT(*) as product_count, SUM(stock) as total_stock, SUM(stock*harga) as total_value FROM data_barang GROUP BY kategori ORDER BY product_count DESC")->fetchAll();
    }
}
