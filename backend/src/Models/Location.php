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
        // Return distinct box+laci combinations as locations
        $sql = "SELECT 
                    CONCAT(nomor_box, '-', nomor_laci) as id,
                    nomor_box,
                    nomor_laci,
                    CONCAT('Box ', nomor_box, ' Laci ', nomor_laci) as name,
                    COUNT(*) as product_count,
                    SUM(stock) as total_stock
                FROM data_barang
                GROUP BY nomor_box, nomor_laci
                ORDER BY CAST(nomor_box AS UNSIGNED), CAST(nomor_laci AS UNSIGNED)";
        return $pdo->query($sql)->fetchAll();
    }

    public static function getBoxes(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT nomor_box as id, nomor_box as name, nomor_box, COUNT(*) as product_count FROM data_barang GROUP BY nomor_box ORDER BY CAST(nomor_box AS UNSIGNED)")->fetchAll();
    }

    public static function getLaciByBox(string $box): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT nomor_laci as id, nomor_laci as name, nomor_laci, COUNT(*) as product_count FROM data_barang WHERE nomor_box = :box GROUP BY nomor_laci ORDER BY CAST(nomor_laci AS UNSIGNED)");
        $stmt->execute(['box'=>$box]);
        return $stmt->fetchAll();
    }

    public static function find(string $id): ?array {
        // id is like "1-2" (box-laci)
        $pdo = Database::getConnection();
        $parts = explode('-', $id);
        if (count($parts) >= 2) {
            $box = $parts[0];
            $laci = $parts[1];
            $stmt = $pdo->prepare("SELECT CONCAT(nomor_box,'-',nomor_laci) as id, nomor_box, nomor_laci, CONCAT('Box ',nomor_box,' Laci ',nomor_laci) as name, COUNT(*) as product_count FROM data_barang WHERE nomor_box = :box AND nomor_laci = :laci GROUP BY nomor_box, nomor_laci");
            $stmt->execute(['box'=>$box,'laci'=>$laci]);
            return $stmt->fetch() ?: null;
        }
        // fallback: find by box only
        $stmt = $pdo->prepare("SELECT nomor_box as id, nomor_box as name, nomor_box, COUNT(*) as product_count FROM data_barang WHERE nomor_box = :box GROUP BY nomor_box");
        $stmt->execute(['box'=>$id]);
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
        $oldParts = explode('-', $id);
        if (count($oldParts) < 2) return false;
        $oldBox = $oldParts[0];
        $oldLaci = $oldParts[1];
        $newBox = $data['nomor_box'] ?? $data['name'] ?? $oldBox;
        $newLaci = $data['nomor_laci'] ?? $oldLaci;

        // If newBox contains dash format, split
        if (strpos($newBox, '-') !== false) {
            $p = explode('-', $newBox);
            $newBox = $p[0];
            $newLaci = $p[1] ?? $newLaci;
        }

        $stmt = $pdo->prepare("UPDATE data_barang SET nomor_box = :nb, nomor_laci = :nl WHERE nomor_box = :ob AND nomor_laci = :ol");
        return $stmt->execute(['nb'=>$newBox,'nl'=>$newLaci,'ob'=>$oldBox,'ol'=>$oldLaci]);
    }

    public static function delete(string $id): bool {
        // Deleting location means deleting all products in that location? Dangerous. Better to prevent if has products.
        // For safety, we return false if has products, or we delete none and say success but no action.
        // Here we choose: if location has products, forbid deletion.
        $pdo = Database::getConnection();
        $parts = explode('-', $id);
        if (count($parts) < 2) return false;
        $box = $parts[0];
        $laci = $parts[1];
        $check = $pdo->prepare("SELECT COUNT(*) as c FROM data_barang WHERE nomor_box = :b AND nomor_laci = :l");
        $check->execute(['b'=>$box,'l'=>$laci]);
        $c = $check->fetch()['c'] ?? 0;
        if ($c > 0) throw new \Exception("Cannot delete location with $c products. Move or delete products first.");
        return true;
    }

    public static function productsByLocation(string $box, ?string $laci = null): array {
        $pdo = Database::getConnection();
        if ($laci) {
            $stmt = $pdo->prepare("SELECT * FROM data_barang WHERE nomor_box = :b AND nomor_laci = :l ORDER BY nama");
            $stmt->execute(['b'=>$box,'l'=>$laci]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM data_barang WHERE nomor_box = :b ORDER BY CAST(nomor_laci AS UNSIGNED), nama");
            $stmt->execute(['b'=>$box]);
        }
        return $stmt->fetchAll();
    }
}
