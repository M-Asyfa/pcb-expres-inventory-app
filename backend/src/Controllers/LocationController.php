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
        if (!$input) Response::error('Invalid JSON', 400);
        // Sanitize box/laci
        if (isset($input['nomor_box'])) $input['nomor_box'] = trim((string)$input['nomor_box']);
        if (isset($input['nomor_laci'])) $input['nomor_laci'] = trim((string)$input['nomor_laci']);
        try {
            Location::update($id, $input);
            $newBox = $input['nomor_box'] ?? null;
            $newLaci = $input['nomor_laci'] ?? null;
            $lookupId = ($newBox !== null && $newLaci !== null) ? ($newBox . '-' . $newLaci) : $id;
            // If newBox contains dash already, use as is
            if ($newBox !== null && str_contains($newBox, '-') && $newLaci === null) {
                $lookupId = $newBox;
            }
            Response::json(['data' => Location::find($lookupId), 'message' => 'Location updated - all products moved']);
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
