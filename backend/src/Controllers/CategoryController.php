<?php
namespace App\Controllers;

use App\Models\Category;
use App\Core\Response;

class CategoryController {
    public function index(): void {
        Response::json(['data' => Category::all()]);
    }
    public function stats(): void {
        Response::json(['data' => Category::stats()]);
    }
    public function show(array $params): void {
        $cat = Category::find($params['id']);
        if (!$cat) Response::error('Category not found', 404);
        Response::json(['data' => $cat]);
    }
    public function store(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? $input['kategori'] ?? null;
        if (empty($name)) Response::error('Name/kategori required', 422);
        try {
            Category::create(['kategori'=>$name, 'name'=>$name]);
            Response::json(['data' => Category::find($name)], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
    public function update(array $params): void {
        $id = $params['id'];
        if (!Category::find($id)) Response::error('Category not found', 404);
        $input = json_decode(file_get_contents('php://input'), true);
        $newName = $input['name'] ?? $input['kategori'] ?? null;
        if (!$newName) Response::error('New name required', 422);
        try {
            Category::update($id, ['kategori'=>$newName, 'name'=>$newName]);
            Response::json(['data' => Category::find($newName)]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
    public function destroy(array $params): void {
        $id = $params['id'];
        try {
            Category::delete($id);
            Response::json(['message' => 'Deleted']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
