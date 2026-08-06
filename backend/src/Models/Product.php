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
    private static function buildWhere(array $filters, array &$params): string {
        $where = "";
        if (!empty($filters['search'])) {
            $where .= " AND (nama LIKE :search1 OR keterangan_barang LIKE :search2 OR kategori LIKE :search3 OR TRIM(nomor_box) LIKE :search4 OR TRIM(nomor_laci) LIKE :search5)";
            $like = '%' . $filters['search'] . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
        }
        if (!empty($filters['kategori'])) {
            $where .= " AND kategori = :kategori";
            $params['kategori'] = trim($filters['kategori']);
        }
        if (!empty($filters['category_id'])) {
            $where .= " AND kategori = :kategori2";
            $params['kategori2'] = trim($filters['category_id']);
        }
        if (!empty($filters['nomor_box'])) {
            $where .= " AND TRIM(nomor_box) = :nomor_box";
            $params['nomor_box'] = trim($filters['nomor_box']);
        }
        if (!empty($filters['nomor_laci'])) {
            $where .= " AND TRIM(nomor_laci) = :nomor_laci";
            $params['nomor_laci'] = trim($filters['nomor_laci']);
        }
        if (!empty($filters['low_stock'])) {
            $where .= " AND stock <= batas_stock";
        }
        if (!empty($filters['out_of_stock'])) {
            $where .= " AND stock = 0";
        }
        return $where;
    }

    private static function buildOrder(array $filters): string {
        $allowed = [
            'id' => 'id',
            'nama' => 'nama',
            'keterangan' => 'keterangan_barang',
            'kategori' => 'kategori',
            'box' => 'CAST(TRIM(nomor_box) AS UNSIGNED)',
            'nomor_box' => 'CAST(TRIM(nomor_box) AS UNSIGNED)',
            'laci' => 'CAST(TRIM(nomor_laci) AS UNSIGNED)',
            'nomor_laci' => 'CAST(TRIM(nomor_laci) AS UNSIGNED)',
            'harga' => 'harga',
            'stock' => 'stock',
            'batas_stock' => 'batas_stock',
            'updated' => 'updated',
            'total_value' => '(harga * stock)',
            'totalValue' => '(harga * stock)'
        ];
        $sortBy = $filters['sort_by'] ?? null;
        $sortDir = strtoupper($filters['sort_dir'] ?? 'DESC');
        if (!in_array($sortDir, ['ASC','DESC'], true)) $sortDir = 'DESC';

        if ($sortBy && isset($allowed[$sortBy])) {
            // Secondary sort ensures stable pagination
            return " ORDER BY {$allowed[$sortBy]} $sortDir, id DESC";
        }
        return " ORDER BY updated DESC, id DESC";
    }

    public static function all(array $filters = []): array {
        $pdo = Database::getConnection();
        $params = [];
        $sql = "SELECT * FROM data_barang WHERE 1=1";
        $sql .= self::buildWhere($filters, $params);
        $sql .= self::buildOrder($filters);
        
        if (isset($filters['per_page'])) {
            $perPage = max(1, min(100, (int)$filters['per_page']));
            $page = max(1, (int)($filters['page'] ?? 1));
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT $perPage OFFSET $offset";
        } elseif (!empty($filters['limit'])) {
            $limit = max(1, min(500, (int)$filters['limit']));
            $offset = max(0, (int)($filters['offset'] ?? 0));
            $sql .= $offset > 0 ? " LIMIT $limit OFFSET $offset" : " LIMIT $limit";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();
        $params = [];
        $sql = "SELECT COUNT(*) as cnt FROM data_barang WHERE 1=1";
        $sql .= self::buildWhere($filters, $params);
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
        // Include foto if column exists
        $hasFoto = self::hasFotoColumn();
        if ($hasFoto) {
            $sql = "INSERT INTO data_barang (nama, kategori, keterangan_barang, nomor_box, nomor_laci, harga, stock, batas_stock, foto)
                    VALUES (:nama, :kategori, :keterangan_barang, :nomor_box, :nomor_laci, :harga, :stock, :batas_stock, :foto)";
        } else {
            $sql = "INSERT INTO data_barang (nama, kategori, keterangan_barang, nomor_box, nomor_laci, harga, stock, batas_stock)
                    VALUES (:nama, :kategori, :keterangan_barang, :nomor_box, :nomor_laci, :harga, :stock, :batas_stock)";
        }
        $stmt = $pdo->prepare($sql);
        $box = isset($data['nomor_box']) ? trim((string)$data['nomor_box']) : '0';
        $laci = isset($data['nomor_laci']) ? trim((string)$data['nomor_laci']) : '1';
        if ($box === '') $box = '0';
        if ($laci === '') $laci = '1';
        $params = [
            'nama' => trim($data['nama']),
            'kategori' => isset($data['kategori']) ? trim($data['kategori']) : '',
            'keterangan_barang' => isset($data['keterangan_barang']) ? trim($data['keterangan_barang']) : (trim($data['nama']) ?? ''),
            'nomor_box' => $box,
            'nomor_laci' => $laci,
            'harga' => $data['harga'] ?? 0,
            'stock' => $data['stock'] ?? 0,
            'batas_stock' => $data['batas_stock'] ?? 10
        ];
        if ($hasFoto) $params['foto'] = $data['foto'] ?? null;
        $stmt->execute($params);
        $id = (int)$pdo->lastInsertId();

        // Ensure kategori exists in kategori table
        if (!empty($data['kategori'])) {
            try {
                $pdo->prepare("INSERT IGNORE INTO kategori (kategori) VALUES (:k)")->execute(['k' => $data['kategori']]);
            } catch (\Exception $e) {}
        }

        return $id;
    }

    public static function hasFotoColumn(): bool {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->query("SHOW COLUMNS FROM data_barang LIKE 'foto'");
            $exists = $stmt && $stmt->fetch() !== false;
            if (!$exists) {
                // Auto-migrate for existing DBs that were created before foto feature
                try {
                    $pdo->exec("ALTER TABLE data_barang ADD COLUMN IF NOT EXISTS foto VARCHAR(500) DEFAULT NULL");
                    // Re-check after attempt
                    $stmt2 = $pdo->query("SHOW COLUMNS FROM data_barang LIKE 'foto'");
                    return $stmt2 && $stmt2->fetch() !== false;
                } catch (\Exception $e2) {
                    // Try without IF NOT EXISTS for older MariaDB
                    try {
                        $pdo->exec("ALTER TABLE data_barang ADD COLUMN foto VARCHAR(500) DEFAULT NULL");
                        return true;
                    } catch (\Exception $e3) {
                        return false;
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function ensureFotoColumn(): bool {
        return self::hasFotoColumn();
    }

    public static function updateFoto(int $id, ?string $path): bool {
        $pdo = Database::getConnection();
        if (!self::hasFotoColumn()) return false;
        $stmt = $pdo->prepare("UPDATE data_barang SET foto = :foto WHERE id = :id");
        return $stmt->execute(['foto'=>$path,'id'=>$id]);
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $fields = [];
        $params = ['id' => $id];
        $allowed = ['nama','kategori','keterangan_barang','nomor_box','nomor_laci','harga','stock','batas_stock','foto'];
        
        // Only allow foto if column exists
        $hasFoto = self::hasFotoColumn();
        foreach ($allowed as $field) {
            if ($field === 'foto' && !$hasFoto) continue;
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
        // TRIM to handle dirty "84 " values
        return $pdo->query("SELECT TRIM(nomor_box) as nomor_box, COUNT(*) as product_count, SUM(stock) as total_stock FROM data_barang GROUP BY TRIM(nomor_box) ORDER BY CAST(TRIM(nomor_box) AS UNSIGNED)")->fetchAll();
    }
}
