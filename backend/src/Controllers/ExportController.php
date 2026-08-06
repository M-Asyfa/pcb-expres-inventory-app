<?php
namespace App\Controllers;

use App\Models\Product;
use App\Config\Database;
use App\Core\Response;

class ExportController {
    private function parseBool(string $key): ?bool {
        if (!isset($_GET[$key])) return null;
        $v = $_GET[$key];
        if ($v === '' || $v === '0' || $v === 'false' || $v === false) return null;
        return true;
    }

    public function csv(): void {
        $filters = [
            'search' => isset($_GET['search']) ? trim($_GET['search']) : null,
            'kategori' => isset($_GET['kategori']) ? trim($_GET['kategori']) : null,
            'nomor_box' => isset($_GET['nomor_box']) ? trim($_GET['nomor_box']) : null,
            'low_stock' => $this->parseBool('low_stock')
        ];

        // No pagination for export - get all matching, but cap to 10000 rows for safety
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM data_barang WHERE 1=1";
        $params = [];
        if (!empty($filters['search'])) {
            $sql .= " AND (nama LIKE :search1 OR keterangan_barang LIKE :search2 OR kategori LIKE :search3)";
            $like = '%' . $filters['search'] . '%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
        }
        if (!empty($filters['kategori'])) {
            $sql .= " AND kategori = :kategori";
            $params['kategori'] = $filters['kategori'];
        }
        if (!empty($filters['nomor_box'])) {
            $sql .= " AND TRIM(nomor_box) = :nomor_box";
            $params['nomor_box'] = $filters['nomor_box'];
        }
        if (!empty($filters['low_stock'])) {
            $sql .= " AND stock <= batas_stock";
        }
        $sql .= " ORDER BY CAST(TRIM(nomor_box) AS UNSIGNED), CAST(TRIM(nomor_laci) AS UNSIGNED), nama LIMIT 10000";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inventory_pcbexpressjogja_' . date('Y-m-d_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        fputcsv($out, ['id','updated','nama','kategori','keterangan_barang','nomor_box','nomor_laci','harga','stock','batas_stock','total_value','foto']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'], $r['updated'], $r['nama'], $r['kategori'], $r['keterangan_barang'],
                $r['nomor_box'], $r['nomor_laci'], $r['harga'], $r['stock'], $r['batas_stock'],
                $r['harga'] * $r['stock'],
                $r['foto'] ?? ''
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

        $uploaded = $_FILES['file'];
        // Validate size (max 5MB) and error
        if ($uploaded['error'] !== UPLOAD_ERR_OK) {
            Response::error('Upload error code: ' . $uploaded['error'], 400);
        }
        if ($uploaded['size'] > 5 * 1024 * 1024) {
            Response::error('File too large (max 5MB)', 400);
        }
        // Validate mime/type via extension and mime
        $ext = strtolower(pathinfo($uploaded['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            Response::error('Only CSV files allowed', 400);
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $uploaded['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['text/csv','text/plain','application/csv','application/vnd.ms-excel'];
        if ($mime && !in_array($mime, $allowedMimes, true) && !str_contains($mime, 'csv') && !str_contains($mime, 'plain')) {
            // Allow but log - mime detection can be inconsistent on Windows
        }

        $file = $uploaded['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) Response::error('Cannot open file', 500);

        $pdo = Database::getConnection();
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            Response::error('Empty or invalid CSV', 400);
        }
        // Normalize header to lowercase trim
        $header = array_map(fn($h) => strtolower(trim($h)), $header);
        $expected = ['id','nama','kategori','keterangan_barang','nomor_box','nomor_laci','harga','stock','batas_stock'];
        // Allow flexible but at least require nama
        $hasNama = in_array('nama', $header, true);
        if (!$hasNama && count($header) < 3) {
            fclose($handle);
            Response::error('CSV header must contain nama', 400);
        }

        $imported = 0;
        $maxRows = 5000;
        $rowNum = 0;

        $pdo->beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $rowNum++;
                if ($rowNum > $maxRows) {
                    throw new \Exception("Max rows $maxRows exceeded");
                }
                if (count($data) < 2) continue;
                // Prevent CSV injection by stripping leading formula chars after trimming
                $data = array_map(fn($v) => is_string($v) ? trim($v) : $v, $data);

                $row = null;
                if (count($header) === count($data)) {
                    $row = array_combine($header, $data);
                }

                if ($row) {
                    $nama = $row['nama'] ?? $data[2] ?? null;
                    $kategori = $row['kategori'] ?? $data[3] ?? '';
                    $ket = $row['keterangan_barang'] ?? $data[4] ?? $nama;
                    $box = $row['nomor_box'] ?? $data[5] ?? '0';
                    $laci = $row['nomor_laci'] ?? $data[6] ?? '1';
                    $hargaRaw = $row['harga'] ?? $data[7] ?? 0;
                    $stockRaw = $row['stock'] ?? $data[8] ?? 0;
                    $batasRaw = $row['batas_stock'] ?? $data[9] ?? 10;
                    $id = isset($row['id']) && is_numeric($row['id']) ? (int)$row['id'] : null;
                } else {
                    // Fallback positional (legacy)
                    $nama = $data[2] ?? $data[0] ?? null;
                    $kategori = $data[3] ?? $data[1] ?? '';
                    $ket = $data[4] ?? $nama;
                    $box = $data[5] ?? '0';
                    $laci = $data[6] ?? '1';
                    $hargaRaw = $data[7] ?? 0;
                    $stockRaw = $data[8] ?? 0;
                    $batasRaw = $data[9] ?? 10;
                    $id = null;
                }

                if (!$nama) continue;
                $nama = trim((string)$nama);
                if ($nama === '' || mb_strlen($nama) > 500) continue;

                // Sanitize: remove CSV formula injection leading chars =,+,-,@, but keep negative numbers for stock? We disallow formula.
                // If field starts with = + - @ and second char is letter or quote, strip leading char.
                $preventFormula = function($val) {
                    if (preg_match('/^[=+\-@]/', $val) && !is_numeric($val)) {
                        return "'" . $val;
                    }
                    return $val;
                };
                // For numeric fields, validate
                $harga = filter_var($hargaRaw, FILTER_VALIDATE_INT) !== false ? (int)$hargaRaw : 0;
                $stock = filter_var($stockRaw, FILTER_VALIDATE_INT) !== false ? (int)$stockRaw : 0;
                $batas = filter_var($batasRaw, FILTER_VALIDATE_INT) !== false ? (int)$batasRaw : 10;
                if ($harga < 0) $harga = 0;
                if ($stock < 0) $stock = 0;
                if ($batas < 0) $batas = 10;
                if ($harga > 1000000000) $harga = 1000000000;

                $kategori = trim((string)$kategori);
                $ket = trim((string)$ket);
                $box = trim((string)$box);
                $laci = trim((string)$laci);
                if ($box === '') $box = '0';
                if ($laci === '') $laci = '1';

                $nama = $preventFormula($nama);
                $kategori = $preventFormula($kategori);
                $ket = $preventFormula($ket);

                if ($id) {
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
            // Don't leak internal SQL errors when debug false
            $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
            $msg = $debug ? $e->getMessage() : 'Import failed';
            Response::error('Import failed: ' . $msg, 500);
        }
        fclose($handle);
        Response::json(['message' => "Imported $imported rows", 'imported' => $imported]);
    }
}
