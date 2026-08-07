<?php
namespace App\Models;

use App\Config\Database;

/**
 * Location model adapted to real schema:
 * In data_barang, location = nomor_box + nomor_laci
 * No separate locations table. We aggregate distinct boxes/laci.
 */
class Location {
    public static function all(): array {
        $pdo = Database::getConnection();
        $sql = "SELECT 
                    CONCAT(TRIM(nomor_box), '-', TRIM(nomor_laci)) as id,
                    TRIM(nomor_box) as nomor_box,
                    TRIM(nomor_laci) as nomor_laci,
                    CONCAT('Box ', TRIM(nomor_box), ' Laci ', TRIM(nomor_laci)) as name,
                    COUNT(*) as product_count,
                    SUM(stock) as total_stock
                FROM data_barang
                GROUP BY TRIM(nomor_box), TRIM(nomor_laci)
                ORDER BY CAST(TRIM(nomor_box) AS UNSIGNED), CAST(TRIM(nomor_laci) AS UNSIGNED)";
        return $pdo->query($sql)->fetchAll();
    }

    public static function getBoxes(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT TRIM(nomor_box) as id, TRIM(nomor_box) as name, TRIM(nomor_box) as nomor_box, COUNT(*) as product_count FROM data_barang GROUP BY TRIM(nomor_box) ORDER BY CAST(TRIM(nomor_box) AS UNSIGNED)")->fetchAll();
    }

    public static function getLaciByBox(string $box): array {
        $pdo = Database::getConnection();
        $box = trim($box);
        $boxInt = is_numeric($box) ? (int)$box : null;
        if ($boxInt !== null) {
            // Match both exact trimmed string and numeric equivalent (handles "01" vs "1", "84 " vs "84")
            $stmt = $pdo->prepare("
                SELECT TRIM(nomor_laci) as id, TRIM(nomor_laci) as name, TRIM(nomor_laci) as nomor_laci, COUNT(*) as product_count
                FROM data_barang
                WHERE TRIM(nomor_box) = :box OR CAST(TRIM(nomor_box) AS UNSIGNED) = :boxInt
                GROUP BY TRIM(nomor_laci)
                ORDER BY CAST(TRIM(nomor_laci) AS UNSIGNED)
            ");
            $stmt->execute(['box'=>$box,'boxInt'=>$boxInt]);
        } else {
            $stmt = $pdo->prepare("SELECT TRIM(nomor_laci) as id, TRIM(nomor_laci) as name, TRIM(nomor_laci) as nomor_laci, COUNT(*) as product_count FROM data_barang WHERE TRIM(nomor_box) = :box GROUP BY TRIM(nomor_laci) ORDER BY CAST(TRIM(nomor_laci) AS UNSIGNED)");
            $stmt->execute(['box'=>$box]);
        }
        return $stmt->fetchAll();
    }

    public static function find(string $id): ?array {
        $id = trim($id);
        $pdo = Database::getConnection();
        $parts = explode('-', $id);
        if (count($parts) >= 2) {
            $box = trim($parts[0]);
            $laci = trim($parts[1]);
            $boxInt = is_numeric($box) ? (int)$box : null;
            $laciInt = is_numeric($laci) ? (int)$laci : null;
            if ($boxInt !== null && $laciInt !== null) {
                $stmt = $pdo->prepare("
                    SELECT CONCAT(TRIM(nomor_box),'-',TRIM(nomor_laci)) as id, TRIM(nomor_box) as nomor_box, TRIM(nomor_laci) as nomor_laci, CONCAT('Box ',TRIM(nomor_box),' Laci ',TRIM(nomor_laci)) as name, COUNT(*) as product_count, COALESCE(SUM(stock),0) as total_stock
                    FROM data_barang
                    WHERE (TRIM(nomor_box) = :box OR CAST(TRIM(nomor_box) AS UNSIGNED) = :boxInt)
                      AND (TRIM(nomor_laci) = :laci OR CAST(TRIM(nomor_laci) AS UNSIGNED) = :laciInt)
                    GROUP BY TRIM(nomor_box), TRIM(nomor_laci)
                ");
                $stmt->execute(['box'=>$box,'boxInt'=>$boxInt,'laci'=>$laci,'laciInt'=>$laciInt]);
            } else {
                $stmt = $pdo->prepare("SELECT CONCAT(TRIM(nomor_box),'-',TRIM(nomor_laci)) as id, TRIM(nomor_box) as nomor_box, TRIM(nomor_laci) as nomor_laci, CONCAT('Box ',TRIM(nomor_box),' Laci ',TRIM(nomor_laci)) as name, COUNT(*) as product_count, COALESCE(SUM(stock),0) as total_stock FROM data_barang WHERE TRIM(nomor_box) = :box AND TRIM(nomor_laci) = :laci GROUP BY TRIM(nomor_box), TRIM(nomor_laci)");
                $stmt->execute(['box'=>$box,'laci'=>$laci]);
            }
            return $stmt->fetch() ?: null;
        }
        $box = $id;
        $boxInt = is_numeric($box) ? (int)$box : null;
        if ($boxInt !== null) {
            $stmt = $pdo->prepare("SELECT TRIM(nomor_box) as id, TRIM(nomor_box) as name, TRIM(nomor_box) as nomor_box, COUNT(*) as product_count FROM data_barang WHERE TRIM(nomor_box) = :box OR CAST(TRIM(nomor_box) AS UNSIGNED) = :boxInt GROUP BY TRIM(nomor_box)");
            $stmt->execute(['box'=>$box,'boxInt'=>$boxInt]);
        } else {
            $stmt = $pdo->prepare("SELECT TRIM(nomor_box) as id, TRIM(nomor_box) as name, TRIM(nomor_box) as nomor_box, COUNT(*) as product_count FROM data_barang WHERE TRIM(nomor_box) = :box GROUP BY TRIM(nomor_box)");
            $stmt->execute(['box'=>$box]);
        }
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        // Locations are virtual, created implicitly by inserting products with box/laci.
        // For compatibility, we insert a placeholder product? Better to just return success if box/laci provided.
        // We'll create an entry in a pseudo way: if no products exist with that box/laci, we insert a dummy kategori entry? 
        // Instead, we treat create as noop - just ensure box is valid format.
        // To make it explicit: creating location means nothing in DB until product uses it.
        // We'll insert into kategori? No.
        // Simplest: return success, frontend should guide user to create product with that location.
        // For better UX, we can insert a placeholder product with name="__EMPTY__" that user can delete? Not ideal.
        // Let's just return 1 and let API return the requested location as if it exists.
        return 1;
    }

    public static function update(string $id, array $data): bool {
        $pdo = Database::getConnection();
        $oldParts = explode('-', trim($id));
        if (count($oldParts) < 2) return false;
        $oldBox = trim($oldParts[0]);
        $oldLaci = trim($oldParts[1]);
        $newBox = isset($data['nomor_box']) ? trim($data['nomor_box']) : (isset($data['name']) ? trim($data['name']) : $oldBox);
        $newLaci = isset($data['nomor_laci']) ? trim($data['nomor_laci']) : $oldLaci;

        if (strpos($newBox, '-') !== false) {
            $p = explode('-', $newBox);
            $newBox = trim($p[0]);
            $newLaci = trim($p[1] ?? $newLaci);
        }
        if ($newBox === '' || $newLaci === '') return false;

        $stmt = $pdo->prepare("UPDATE data_barang SET nomor_box = :nb, nomor_laci = :nl WHERE TRIM(nomor_box) = :ob AND TRIM(nomor_laci) = :ol");
        return $stmt->execute(['nb'=>$newBox,'nl'=>$newLaci,'ob'=>$oldBox,'ol'=>$oldLaci]);
    }

    public static function delete(string $id): bool {
        $pdo = Database::getConnection();
        $parts = explode('-', trim($id));
        if (count($parts) < 2) return false;
        $box = trim($parts[0]);
        $laci = trim($parts[1]);
        $check = $pdo->prepare("SELECT COUNT(*) as c FROM data_barang WHERE TRIM(nomor_box) = :b AND TRIM(nomor_laci) = :l");
        $check->execute(['b'=>$box,'l'=>$laci]);
        $c = $check->fetch()['c'] ?? 0;
        if ($c > 0) throw new \Exception("Cannot delete location with $c products. Move or delete products first.");
        return true;
    }

    public static function productsByLocation(string $box, ?string $laci = null): array {
        $pdo = Database::getConnection();
        $box = trim($box);
        $laci = $laci !== null ? trim($laci) : null;
        $boxInt = is_numeric($box) ? (int)$box : null;
        $laciInt = $laci !== null && is_numeric($laci) ? (int)$laci : null;

        // Fix: some show nothing because of leading zeros like "01" vs "1" or spaces "84 " vs "84"
        // We match both exact trimmed and numeric equivalent
        if ($laci !== null && $laci !== '') {
            if ($boxInt !== null && $laciInt !== null) {
                // Numeric fallback: matches "1" = "01" = " 1 "
                $stmt = $pdo->prepare("
                    SELECT * FROM data_barang
                    WHERE (TRIM(nomor_box) = :b OR CAST(TRIM(nomor_box) AS UNSIGNED) = :bInt)
                      AND (TRIM(nomor_laci) = :l OR CAST(TRIM(nomor_laci) AS UNSIGNED) = :lInt)
                    ORDER BY nama
                ");
                $stmt->execute(['b'=>$box,'bInt'=>$boxInt,'l'=>$laci,'lInt'=>$laciInt]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM data_barang WHERE TRIM(nomor_box) = :b AND TRIM(nomor_laci) = :l ORDER BY nama");
                $stmt->execute(['b'=>$box,'l'=>$laci]);
            }
            $rows = $stmt->fetchAll();
            // If still empty, try looser search (handles hidden chars)
            if (empty($rows)) {
                $stmt2 = $pdo->prepare("
                    SELECT * FROM data_barang
                    WHERE TRIM(nomor_box) LIKE :bLike AND TRIM(nomor_laci) LIKE :lLike
                    ORDER BY nama
                ");
                $stmt2->execute(['bLike'=>'%'.$box.'%','lLike'=>'%'.$laci.'%']);
                $rows = $stmt2->fetchAll();
            }
            return $rows;
        } else {
            if ($boxInt !== null) {
                $stmt = $pdo->prepare("
                    SELECT * FROM data_barang
                    WHERE TRIM(nomor_box) = :b OR CAST(TRIM(nomor_box) AS UNSIGNED) = :bInt
                    ORDER BY CAST(TRIM(nomor_laci) AS UNSIGNED), nama
                ");
                $stmt->execute(['b'=>$box,'bInt'=>$boxInt]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM data_barang WHERE TRIM(nomor_box) = :b ORDER BY CAST(TRIM(nomor_laci) AS UNSIGNED), nama");
                $stmt->execute(['b'=>$box]);
            }
            return $stmt->fetchAll();
        }
    }
}
