<?php
namespace App\Controllers;

use App\Models\Product;
use App\Config\Database;
use App\Core\Response;

class ExportController {
    public function csv(): void {
        $filters = [
            'search' => $_GET['search'] ?? null,
            'kategori' => $_GET['kategori'] ?? null,
            'nomor_box' => $_GET['nomor_box'] ?? null,
            'low_stock' => isset($_GET['low_stock']) ? true : null
        ];

        // No pagination for export - get all matching
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM data_barang WHERE 1=1";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (nama LIKE :search OR keterangan_barang LIKE :search OR kategori LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['kategori'])) {
            $sql .= " AND kategori = :kategori";
            $params['kategori'] = $filters['kategori'];
        }
        if (!empty($filters['nomor_box'])) {
            $sql .= " AND nomor_box = :nomor_box";
            $params['nomor_box'] = $filters['nomor_box'];
        }
        if (!empty($filters['low_stock'])) {
            $sql .= " AND stock <= batas_stock";
        }
        $sql .= " ORDER BY CAST(nomor_box AS UNSIGNED), CAST(nomor_laci AS UNSIGNED), nama";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventory_pcbexpressjogja_' . date('Y-m-d_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($out, ['id','updated','nama','kategori','keterangan_barang','nomor_box','nomor_laci','harga','stock','batas_stock','total_value']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['updated'], $r['nama'], $r['kategori'], $r['keterangan_barang'],
                $r['nomor_box'], $r['nomor_laci'], $r['harga'], $r['stock'], $r['batas_stock'],
                $r['harga'] * $r['stock']
            ]);
        }
        fclose($out);
        exit;
    }

    public function import(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('POST only', 405);
        }
        if (!isset($_FILES['file'])) {
            Response::error('No file uploaded', 400);
        }
        $file = $_FILES['file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) Response::error('Cannot open file', 500);

        $pdo = Database::getConnection();
        $header = fgetcsv($handle);
        $imported = 0;
        $errors = [];

        $pdo->beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) < 8) continue;
                // Support both our export format and simplified
                $row = array_combine($header, $data) ?: null;
                if ($row) {
                    $nama = $row['nama'] ?? $data[2] ?? null;
                    $kategori = $row['kategori'] ?? $data[3] ?? '';
                    $ket = $row['keterangan_barang'] ?? $data[4] ?? $nama;
                    $box = $row['nomor_box'] ?? $data[5] ?? '0';
                    $laci = $row['nomor_laci'] ?? $data[6] ?? '1';
                    $harga = (int)($row['harga'] ?? $data[7] ?? 0);
                    $stock = (int)($row['stock'] ?? $data[8] ?? 0);
                    $batas = (int)($row['batas_stock'] ?? $data[9] ?? 10);
                    $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
                } else {
                    $nama = $data[2] ?? null;
                    $kategori = $data[3] ?? '';
                    $ket = $data[4] ?? $nama;
                    $box = $data[5] ?? '0';
                    $laci = $data[6] ?? '1';
                    $harga = (int)($data[7] ?? 0);
                    $stock = (int)($data[8] ?? 0);
                    $batas = (int)($data[9] ?? 10);
                    $id = null;
                }
                if (!$nama) continue;

                if ($id) {
                    // update if exists
                    $stmt = $pdo->prepare("INSERT INTO data_barang (id,nama,kategori,keterangan_barang,nomor_box,nomor_laci,harga,stock,batas_stock) VALUES (:id,:nama,:kat,:ket,:box,:laci,:harga,:stock,:batas) ON DUPLICATE KEY UPDATE nama=VALUES(nama),kategori=VALUES(kategori),keterangan_barang=VALUES(keterangan_barang),nomor_box=VALUES(nomor_box),nomor_laci=VALUES(nomor_laci),harga=VALUES(harga),stock=VALUES(stock),batas_stock=VALUES(batas_stock)");
                    $stmt->execute(['id'=>$id,'nama'=>$nama,'kat'=>$kategori,'ket'=>$ket,'box'=>$box,'laci'=>$laci,'harga'=>$harga,'stock'=>$stock,'batas'=>$batas]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO data_barang (nama,kategori,keterangan_barang,nomor_box,nomor_laci,harga,stock,batas_stock) VALUES (:nama,:kat,:ket,:box,:laci,:harga,:stock,:batas)");
                    $stmt->execute(['nama'=>$nama,'kat'=>$kategori,'ket'=>$ket,'box'=>$box,'laci'=>$laci,'harga'=>$harga,'stock'=>$stock,'batas'=>$batas]);
                }
                if (!empty($kategori)) {
                    $pdo->prepare("INSERT IGNORE INTO kategori (kategori) VALUES (:k)")->execute(['k'=>$kategori]);
                }
                $imported++;
            }
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            fclose($handle);
            Response::error('Import failed: ' . $e->getMessage(), 500);
        }
        fclose($handle);
        Response::json(['message' => "Imported $imported rows", 'imported' => $imported]);
    }
}
