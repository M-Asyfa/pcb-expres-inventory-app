<?php
namespace App\Controllers;

use App\Models\Product;
use App\Core\Response;

class ProductController {
    
    private function parseBoolFilter(string $key): ?bool {
        if (!isset($_GET[$key])) return null;
        $v = $_GET[$key];
        if ($v === '' || $v === '0' || $v === 'false' || $v === false) return null;
        if ($v === '1' || $v === 'true' || $v === true || $v === 'on') return true;
        // If param exists without value like ?low_stock, treat as true
        return true;
    }

    public function index(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 20)));
        
        $filters = [
            'search' => isset($_GET['search']) ? trim($_GET['search']) : null,
            'kategori' => $_GET['kategori'] ?? null,
            'category_id' => $_GET['category_id'] ?? null, // compat
            'nomor_box' => isset($_GET['nomor_box']) ? trim($_GET['nomor_box']) : null,
            'nomor_laci' => isset($_GET['nomor_laci']) ? trim($_GET['nomor_laci']) : null,
            'low_stock' => $this->parseBoolFilter('low_stock'),
            'out_of_stock' => $this->parseBoolFilter('out_of_stock'),
            'page' => $page,
            'per_page' => $perPage,
            'sort_by' => $_GET['sort_by'] ?? null,
            'sort_dir' => $_GET['sort_dir'] ?? null
        ];

        // Handle legacy limit param
        if (isset($_GET['limit']) && !isset($_GET['page'])) {
            $filters['limit'] = max(1, min(500, (int)$_GET['limit']));
            unset($filters['page'], $filters['per_page']);
            $products = Product::all($filters);
            Response::json(['data' => $products, 'total' => count($products)]);
            return;
        }

        $products = Product::all($filters);
        $total = Product::count($filters);
        $totalPages = (int)ceil($total / $perPage);

        Response::json([
            'data' => $products,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'count' => count($products)
            ]
        ]);
    }

    public function show(array $params): void {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) {
            Response::error('Barang not found', 404);
        }
        $product['history'] = Product::getStockHistory($id);
        Response::json(['data' => $product]);
    }

    public function stats(): void {
        Response::json(['data' => Product::getStats()]);
    }

    public function lowStock(): void {
        Response::json(['data' => Product::getLowStock()]);
    }

    public function categoriesStats(): void {
        Response::json(['data' => Product::getCategoriesStats()]);
    }

    public function boxesStats(): void {
        Response::json(['data' => Product::getBoxesStats()]);
    }

    private function sanitizeInput(array $input): array {
        // Trim strings and normalize
        $out = [];
        foreach ($input as $k => $v) {
            if (is_string($v)) {
                $out[$k] = trim($v);
            } else {
                $out[$k] = $v;
            }
        }
        // Ensure numeric fields are valid ints
        foreach (['harga','stock','batas_stock'] as $numField) {
            if (isset($out[$numField])) {
                if (!is_numeric($out[$numField])) {
                    Response::error("Field $numField must be numeric", 422);
                }
                $out[$numField] = (int)$out[$numField];
                if ($numField !== 'harga' && $out[$numField] < 0) {
                    Response::error("Field $numField cannot be negative", 422);
                }
                if ($numField === 'harga' && $out[$numField] < 0) {
                    Response::error("Harga cannot be negative", 422);
                }
            }
        }
        // Normalize box/laci - trim spaces, remove empty
        if (isset($out['nomor_box'])) {
            $out['nomor_box'] = trim((string)$out['nomor_box']);
            if ($out['nomor_box'] === '') $out['nomor_box'] = '0';
        }
        if (isset($out['nomor_laci'])) {
            $out['nomor_laci'] = trim((string)$out['nomor_laci']);
            if ($out['nomor_laci'] === '') $out['nomor_laci'] = '1';
        }
        if (isset($out['nama'])) {
            $len = function_exists('mb_strlen') ? mb_strlen($out['nama']) : strlen($out['nama']);
            if ($len > 500) Response::error('Nama too long (max 500)', 422);
        }
        return $out;
    }

    public function store(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) Response::error('Invalid JSON', 400);

        $required = ['nama'];
        foreach ($required as $field) {
            if (empty($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                Response::error("Field $field is required", 422);
            }
        }

        $input = $this->sanitizeInput($input);
        $id = Product::create($input);
        $product = Product::find($id);
        Response::json(['data' => $product, 'message' => 'Barang created'], 201);
    }

    public function update(array $params): void {
        $id = (int)$params['id'];
        if (!Product::find($id)) Response::error('Barang not found', 404);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) Response::error('Invalid JSON', 400);

        $input = $this->sanitizeInput($input);
        Product::update($id, $input);
        Response::json(['data' => Product::find($id), 'message' => 'Barang updated']);
    }

    public function destroy(array $params): void {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) Response::error('Barang not found', 404);
        // Delete associated photo file if exists
        if (!empty($product['foto'])) {
            $this->deletePhotoFile($product['foto']);
        }
        Product::delete($id);
        Response::json(['message' => 'Barang deleted']);
    }

    private function getUploadDir(): string {
        $dir = __DIR__ . '/../../public/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    private function deletePhotoFile(string $path): void {
        // $path like uploads/filename or /uploads/filename or full url
        if (str_starts_with($path, 'http')) return;
        $path = ltrim($path, '/');
        // Only allow uploads/ prefix for safety
        if (!str_starts_with($path, 'uploads/')) {
            // Legacy may store just filename
            $path = 'uploads/' . basename($path);
        }
        $full = __DIR__ . '/../../public/' . $path;
        $realUpload = realpath($this->getUploadDir());
        $realTarget = realpath(dirname($full)) ?: dirname($full);
        // Prevent directory traversal - must be inside uploads
        if ($realUpload && str_contains($realTarget, $realUpload) || str_starts_with($realTarget, $realUpload)) {
            if (file_exists($full) && is_file($full)) {
                @unlink($full);
            }
        } else {
            // If realpath fails but we have uploads prefix, try direct unlink with basename check
            if (file_exists($full) && is_file($full)) {
                @unlink($full);
            }
        }
    }

    public function uploadPhoto(array $params): void {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) Response::error('Barang not found', 404);

        // Auto-ensure foto column exists for old DBs
        if (!Product::ensureFotoColumn()) {
            Response::error('Foto column missing and auto-migration failed. Please run: ALTER TABLE data_barang ADD COLUMN foto VARCHAR(500) DEFAULT NULL', 500);
        }

        if (!isset($_FILES['photo']) && !isset($_FILES['foto'])) {
            Response::error('No file uploaded (field name must be photo)', 400);
        }
        $file = $_FILES['photo'] ?? $_FILES['foto'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('Upload error: ' . $file['error'], 400);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            Response::error('File too large max 5MB', 400);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg','image/png','image/webp','image/gif','image/jpg'];
        if (!in_array($mime, $allowedMimes, true)) {
            Response::error('Only JPG, PNG, WEBP, GIF allowed. Got: ' . $mime, 400);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowedExt, true)) {
            // Derive from mime
            $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            $ext = $map[$mime] ?? 'jpg';
        }

        $uploadDir = $this->getUploadDir();
        $filename = sprintf('product_%d_%s_%s.%s', $id, date('YmdHis'), bin2hex(random_bytes(4)), $ext);
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Response::error('Failed to move uploaded file', 500);
        }

        // Delete old photo if exists
        if (!empty($product['foto'])) {
            $this->deletePhotoFile($product['foto']);
        }

        $relativePath = 'uploads/' . $filename;
        Product::updateFoto($id, $relativePath);

        Response::json(['data' => Product::find($id), 'message' => 'Photo uploaded', 'foto' => $relativePath]);
    }

    public function deletePhoto(array $params): void {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) Response::error('Barang not found', 404);
        if (empty($product['foto'])) {
            Response::error('No photo to delete', 404);
        }
        $this->deletePhotoFile($product['foto']);
        Product::updateFoto($id, null);
        Response::json(['data' => Product::find($id), 'message' => 'Photo deleted']);
    }

    public function adjustStock(array $params): void {
        $id = (int)$params['id'];
        $product = Product::find($id);
        if (!$product) Response::error('Barang not found', 404);

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['quantity']) || !isset($input['type'])) {
            Response::error('quantity and type (in/out/adjustment) required', 422);
        }

        $type = $input['type'];
        $qtyChange = (int)$input['quantity'];
        $reason = $input['reason'] ?? '';
        $current = (int)$product['stock'];

        if ($qtyChange <= 0 && $type !== 'adjustment') Response::error('Quantity must be >0', 422);
        if (!in_array($type, ['in','out','adjustment'])) Response::error('Invalid type', 422);

        $newQty = $current;
        if ($type === 'in') $newQty = $current + $qtyChange;
        else if ($type === 'out') {
            $newQty = $current - $qtyChange;
            if ($newQty < 0) Response::error('Not enough stock', 400);
        } else {
            $newQty = $qtyChange; // adjustment sets absolute
            if ($newQty < 0) Response::error('Stock cannot be negative', 422);
        }

        try {
            Product::updateQuantity($id, $newQty, $type, $qtyChange, $reason);
            Response::json(['data' => Product::find($id), 'message' => 'Stock updated']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
