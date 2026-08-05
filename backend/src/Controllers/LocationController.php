<?php
namespace App\Controllers;

use App\Models\Location;
use App\Core\Response;

class LocationController {
    public function index(): void {
        // Support ?type=boxes or type=full
        $type = $_GET['type'] ?? 'full';
        if ($type === 'boxes') {
            Response::json(['data' => Location::getBoxes()]);
        } else {
            Response::json(['data' => Location::all()]);
        }
    }

    public function boxes(): void {
        Response::json(['data' => Location::getBoxes()]);
    }

    public function laciByBox(array $params): void {
        $box = $params['box'];
        Response::json(['data' => Location::getLaciByBox($box)]);
    }

    public function show(array $params): void {
        $loc = Location::find($params['id']);
        if (!$loc) Response::error('Location not found', 404);
        Response::json(['data' => $loc]);
    }

    public function products(array $params): void {
        $box = $params['box'] ?? null;
        $laci = $params['laci'] ?? null;
        if (!$box) Response::error('Box required', 422);
        // If id contains dash, parse
        if (strpos($box, '-') !== false && !$laci) {
            $parts = explode('-', $box);
            $box = $parts[0];
            $laci = $parts[1] ?? null;
        }
        Response::json(['data' => Location::productsByLocation($box, $laci)]);
    }

    public function store(): void {
        // Virtual creation
        Response::json(['message' => 'Location is virtual based on box/laci. Create a product with that box/laci to create location.', 'data' => Location::getBoxes()], 201);
    }

    public function update(array $params): void {
        $id = $params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        try {
            Location::update($id, $input ?? []);
            Response::json(['data' => Location::find($input['nomor_box'].'-'.$input['nomor_laci'] ?? $id), 'message' => 'Location updated - all products moved']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function destroy(array $params): void {
        $id = $params['id'];
        try {
            Location::delete($id);
            Response::json(['message' => 'Location deleted (no products remain)']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
