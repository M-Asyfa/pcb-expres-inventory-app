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
    private function decodeId(string $raw): string {
        return trim(urldecode($raw));
    }

    public function show(array $params): void {
        $id = $this->decodeId($params['id']);
        $cat = Category::find($id);
        if (!$cat) Response::error('Category not found', 404);
        Response::json(['data' => $cat]);
    }
    public function store(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? $input['kategori'] ?? null;
        if (empty($name) || trim($name) === '') Response::error('Name/kategori required', 422);
        $name = trim($name);
        try {
            Category::create(['kategori'=>$name, 'name'=>$name]);
            Response::json(['data' => Category::find($name)], 201);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
    public function update(array $params): void {
        $id = $this->decodeId($params['id']);
        if (!Category::find($id)) Response::error('Category not found', 404);
        $input = json_decode(file_get_contents('php://input'), true);
        $newName = $input['name'] ?? $input['kategori'] ?? null;
        if (!$newName || trim($newName) === '') Response::error('New name required', 422);
        $newName = trim($newName);
        try {
            Category::update($id, ['kategori'=>$newName, 'name'=>$newName]);
            Response::json(['data' => Category::find($newName)]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }
    public function destroy(array $params): void {
        $id = $this->decodeId($params['id']);
        try {
            // Ensure category exists or at least try delete (handles orphan)
            Category::delete($id);
            Response::json(['message' => 'Deleted ' . $id]);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
