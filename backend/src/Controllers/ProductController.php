<?php
namespace App\Controllers;

use App\Models\Product;
use App\Core\Response;

class ProductController {
    
    public function index(): void {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 20)));
        
        $filters = [
            'search' => $_GET['search'] ?? null,
            'kategori' => $_GET['kategori'] ?? null,
            'category_id' => $_GET['category_id'] ?? null, // compat
            'nomor_box' => $_GET['nomor_box'] ?? null,
            'nomor_laci' => $_GET['nomor_laci'] ?? null,
            'low_stock' => isset($_GET['low_stock']) ? true : null,
            'out_of_stock' => isset($_GET['out_of_stock']) ? true : null,
            'page' => $page,
            'per_page' => $perPage
        ];

        // Handle legacy limit param
        if (isset($_GET['limit']) && !isset($_GET['page'])) {
            $filters['limit'] = (int)$_GET['limit'];
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

    public function store(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) Response::error('Invalid JSON', 400);

        $required = ['nama'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field $field is required", 422);
            }
        }

        $id = Product::create($input);
        $product = Product::find($id);
        Response::json(['data' => $product, 'message' => 'Barang created'], 201);
    }

    public function update(array $params): void {
        $id = (int)$params['id'];
        if (!Product::find($id)) Response::error('Barang not found', 404);
        
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) Response::error('Invalid JSON', 400);

        Product::update($id, $input);
        Response::json(['data' => Product::find($id), 'message' => 'Barang updated']);
    }

    public function destroy(array $params): void {
        $id = (int)$params['id'];
        if (!Product::find($id)) Response::error('Barang not found', 404);
        Product::delete($id);
        Response::json(['message' => 'Barang deleted']);
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
