<?php
use App\Core\Router;
use App\Controllers\ProductController;
use App\Controllers\CategoryController;
use App\Controllers\LocationController;
use App\Controllers\ExportController;

$router = new Router();

// Health check
$router->get('/api/health', function() {
    \App\Core\Response::json(['status' => 'ok', 'time' => date('c'), 'db' => 'inventory_pcbexpressjogja - data_barang']);
});

// Products (data_barang)
$router->get('/api/products', [ProductController::class, 'index']); // supports ?search=&kategori=&nomor_box=&low_stock=
$router->get('/api/products/stats', [ProductController::class, 'stats']);
$router->get('/api/products/stats/categories', [ProductController::class, 'categoriesStats']);
$router->get('/api/products/stats/boxes', [ProductController::class, 'boxesStats']);
$router->get('/api/products/low-stock', [ProductController::class, 'lowStock']);
$router->get('/api/products/{id}', [ProductController::class, 'show']);
$router->post('/api/products', [ProductController::class, 'store']);
$router->put('/api/products/{id}', [ProductController::class, 'update']);
$router->delete('/api/products/{id}', [ProductController::class, 'destroy']);
$router->post('/api/products/{id}/stock', [ProductController::class, 'adjustStock']);

// Categories (kategori table + aggregated from data_barang)
$router->get('/api/categories', [CategoryController::class, 'index']);
$router->get('/api/categories/stats', [CategoryController::class, 'stats']);
$router->get('/api/categories/{id}', [CategoryController::class, 'show']);
$router->post('/api/categories', [CategoryController::class, 'store']);
$router->put('/api/categories/{id}', [CategoryController::class, 'update']);
$router->delete('/api/categories/{id}', [CategoryController::class, 'destroy']);

// Locations (virtual from nomor_box + nomor_laci)
$router->get('/api/locations', [LocationController::class, 'index']); // ?type=boxes for distinct boxes
$router->get('/api/locations/boxes', [LocationController::class, 'boxes']);
$router->get('/api/locations/box/{box}', [LocationController::class, 'laciByBox']); // laci list for a box
$router->get('/api/locations/box/{box}/laci/{laci}', [LocationController::class, 'products']); // products in box/laci
$router->get('/api/locations/{id}', [LocationController::class, 'show']); // id = box-laci e.g. 1-2
$router->post('/api/locations', [LocationController::class, 'store']);
$router->put('/api/locations/{id}', [LocationController::class, 'update']);
$router->delete('/api/locations/{id}', [LocationController::class, 'destroy']);

// Export / Import
$router->get('/api/export/csv', [ExportController::class, 'csv']);
$router->post('/api/import/csv', [ExportController::class, 'import']);

// Log stock
$router->get('/api/logs', function() {
    $id = $_GET['id'] ?? null;
    if ($id) {
        \App\Core\Response::json(['data' => \App\Models\Product::getStockHistory((int)$id)]);
    } else {
        \App\Core\Response::json(['data' => \App\Models\LogStock::all(200)]);
    }
});

return $router;
