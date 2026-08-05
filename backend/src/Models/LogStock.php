<?php
namespace App\Models;

use App\Config\Database;

class LogStock {
    public static function all(int $limit = 100): array {
        $pdo = Database::getConnection();
        $sql = "SELECT ls.*, db.nama, db.kategori 
                FROM log_stock ls 
                LEFT JOIN data_barang db ON db.id = ls.id 
                ORDER BY ls.waktu DESC LIMIT :limit";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function byProduct(int $productId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM log_stock WHERE id = :id ORDER BY waktu DESC LIMIT 100");
        $stmt->execute(['id'=>$productId]);
        return $stmt->fetchAll();
    }

    public static function recentStats(): array {
        $pdo = Database::getConnection();
        $today = $pdo->query("SELECT COUNT(*) as c FROM log_stock WHERE DATE(waktu) = CURDATE()")->fetch();
        $week = $pdo->query("SELECT COUNT(*) as c FROM log_stock WHERE waktu >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch();
        return [
            'today_movements' => $today['c'] ?? 0,
            'week_movements' => $week['c'] ?? 0
        ];
    }
}
