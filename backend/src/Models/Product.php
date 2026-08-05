<?php
namespace App\Models;

use App\Config\Database;
use PDO;

/**
 * Adapted to real database: inventory_pcbexpressjogja
 * Table: data_barang
 * - id, updated, nama, kategori, keterangan_barang, nomor_box, nomor_laci, harga, stock, batas_stock
 * Plus log_stock for history: no, id (FK data_barang), waktu, stock (delta)
 */
class Product {
    public static function all(array $filters = []): array {
        $pdo = Database::getConnection();
        
        $sql = "SELECT * FROM data_barang WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (nama LIKE :search1 OR keterangan_barang LIKE :search2 OR kategori LIKE :search3 OR nomor_box LIKE :search4 OR nomor_laci LIKE :search5)";
            $like = '%' . $filters['search'] . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
        }
        if (!empty($filters['kategori'])) {
            $sql .= " AND kategori = :kategori";
            $params['kategori'] = $filters['kategori'];
        }
        if (!empty($filters['category_id'])) { // backward compat with old query param
            $sql .= " AND kategori = :kategori2";
            $params['kategori2'] = $filters['category_id'];
        }
        if (!empty($filters['nomor_box'])) {
            $sql .= " AND nomor_box = :nomor_box";
            $params['nomor_box'] = $filters['nomor_box'];
        }
        if (!empty($filters['nomor_laci'])) {
            $sql .= " AND nomor_laci = :nomor_laci";
            $params['nomor_laci'] = $filters['nomor_laci'];
        }
        if (!empty($filters['low_stock'])) {
            $sql .= " AND stock <= batas_stock";
        }
        if (!empty($filters['out_of_stock'])) {
            $sql .= " AND stock = 0";
        }

        $sql .= " ORDER BY updated DESC, id DESC";
        
        // Pagination support
        if (isset($filters['per_page'])) {
            $perPage = max(1, min(100, (int)$filters['per_page']));
            $page = max(1, (int)($filters['page'] ?? 1));
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT $perPage OFFSET $offset";
        } elseif (!empty($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = (int)($filters['offset'] ?? 0);
            if ($offset > 0) {
                $sql .= " LIMIT $limit OFFSET $offset";
            } else {
                $sql .= " LIMIT $limit";
            }
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();
        $sql = "SELECT COUNT(*) as cnt FROM data_barang WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (nama LIKE :search1 OR keterangan_barang LIKE :search2 OR kategori LIKE :search3 OR nomor_box LIKE :search4 OR nomor_laci LIKE :search5)";
            $like = '%' . $filters['search'] . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
        }
        if (!empty($filters['kategori'])) {
            $sql .= " AND kategori = :kategori";
            $params['kategori'] = $filters['kategori'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND kategori = :kategori2";
            $params['kategori2'] = $filters['category_id'];
        }
        if (!empty($filters['nomor_box'])) {
            $sql .= " AND nomor_box = :nomor_box";
            $params['nomor_box'] = $filters['nomor_box'];
        }
        if (!empty($filters['nomor_laci'])) {
            $sql .= " AND nomor_laci = :nomor_laci";
            $params['nomor_laci'] = $filters['nomor_laci'];
        }
        if (!empty($filters['low_stock'])) {
            $sql .= " AND stock <= batas_stock";
        }
        if (!empty($filters['out_of_stock'])) {
            $sql .= " AND stock = 0";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM data_barang WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO data_barang (nama, kategori, keterangan_barang, nomor_box, nomor_laci, harga, stock, batas_stock)
                VALUES (:nama, :kategori, :keterangan_barang, :nomor_box, :nomor_laci, :harga, :stock, :batas_stock)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'nama' => $data['nama'],
            'kategori' => $data['kategori'] ?? '',
            'keterangan_barang' => $data['keterangan_barang'] ?? $data['nama'] ?? '',
            'nomor_box' => $data['nomor_box'] ?? '0',
            'nomor_laci' => $data['nomor_laci'] ?? '1',
            'harga' => $data['harga'] ?? 0,
            'stock' => $data['stock'] ?? 0,
            'batas_stock' => $data['batas_stock'] ?? 10
        ]);
        $id = (int)$pdo->lastInsertId();

        // Ensure kategori exists in kategori table
        if (!empty($data['kategori'])) {
            try {
                $pdo->prepare("INSERT IGNORE INTO kategori (kategori) VALUES (:k)")->execute(['k' => $data['kategori']]);
            } catch (\Exception $e) {}
        }

        return $id;
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $fields = [];
        $params = ['id' => $id];
        $allowed = ['nama','kategori','keterangan_barang','nomor_box','nomor_laci','harga','stock','batas_stock'];
        
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        if (empty($fields)) return false;
        
        $sql = "UPDATE data_barang SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);

        if ($result && isset($data['kategori']) && !empty($data['kategori'])) {
            try {
                $pdo->prepare("INSERT IGNORE INTO kategori (kategori) VALUES (:k)")->execute(['k' => $data['kategori']]);
            } catch (\Exception $e) {}
        }

        return $result;
    }

    public static function delete(int $id): bool {
        $pdo = Database::getConnection();
        // Delete logs first (no FK cascade in original dump)
        $pdo->prepare("DELETE FROM log_stock WHERE id = :id")->execute(['id'=>$id]);
        $stmt = $pdo->prepare("DELETE FROM data_barang WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public static function updateQuantity(int $id, int $newQty, string $type, int $changeQty, string $reason = ''): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $product = self::find($id);
            if (!$product) throw new \Exception("Product not found");
            
            $prevQty = (int)$product['stock'];
            
            $stmt = $pdo->prepare("UPDATE data_barang SET stock = :stock WHERE id = :id");
            $stmt->execute(['stock' => $newQty, 'id' => $id]);
            
            // log_stock: stock column stores delta (can be negative for out)
            // For adjustment, changeQty is already delta (new - old)
            $delta = $changeQty;
            if ($type === 'out') $delta = -$changeQty;
            else if ($type === 'adjustment') $delta = $newQty - $prevQty;

            $stmt2 = $pdo->prepare("INSERT INTO log_stock (id, waktu, stock) VALUES (:id, NOW(), :stock)");
            $stmt2->execute([
                'id' => $id,
                'stock' => $delta
            ]);
            
            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function getStockHistory(int $id): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM log_stock WHERE id = :id ORDER BY waktu DESC LIMIT 100");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public static function getLowStock(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT * FROM data_barang WHERE stock <= batas_stock ORDER BY stock ASC LIMIT 100");
        return $stmt->fetchAll();
    }

    public static function getStats(): array {
        $pdo = Database::getConnection();
        $total = $pdo->query("SELECT COUNT(*) as count, SUM(stock) as total_qty, SUM(stock * harga) as total_value FROM data_barang")->fetch();
        $low = $pdo->query("SELECT COUNT(*) as count FROM data_barang WHERE stock <= batas_stock")->fetch();
        $out = $pdo->query("SELECT COUNT(*) as count FROM data_barang WHERE stock = 0")->fetch();
        $categories = $pdo->query("SELECT COUNT(DISTINCT kategori) as count FROM data_barang")->fetch();
        $boxes = $pdo->query("SELECT COUNT(DISTINCT CONCAT(nomor_box,'-',nomor_laci)) as count FROM data_barang")->fetch();
        return [
            'total_products' => (int)($total['count'] ?? 0),
            'total_quantity' => (int)($total['total_qty'] ?? 0),
            'total_value' => (float)($total['total_value'] ?? 0),
            'low_stock_count' => (int)($low['count'] ?? 0),
            'out_of_stock_count' => (int)($out['count'] ?? 0),
            'category_count' => (int)($categories['count'] ?? 0),
            'location_count' => (int)($boxes['count'] ?? 0)
        ];
    }

    public static function getCategoriesStats(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT kategori, COUNT(*) as product_count, SUM(stock) as total_stock FROM data_barang GROUP BY kategori ORDER BY product_count DESC")->fetchAll();
    }

    public static function getBoxesStats(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT nomor_box, COUNT(*) as product_count, SUM(stock) as total_stock FROM data_barang GROUP BY nomor_box ORDER BY CAST(nomor_box AS UNSIGNED)")->fetchAll();
    }
}
