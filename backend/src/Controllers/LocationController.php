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

    private function decodeParam(?string $raw): ?string {
        if ($raw === null) return null;
        return trim(urldecode($raw));
    }

    public function laciByBox(array $params): void {
        $box = $this->decodeParam($params['box'] ?? '');
        if ($box === '' || $box === null) Response::error('Box required', 422);
        Response::json(['data' => Location::getLaciByBox($box)]);
    }

    public function show(array $params): void {
        $id = $this->decodeParam($params['id'] ?? '');
        $loc = Location::find($id);
        if (!$loc) Response::error('Location not found', 404);
        Response::json(['data' => $loc]);
    }

    public function products(array $params): void {
        $box = $this->decodeParam($params['box'] ?? null);
        $laci = $this->decodeParam($params['laci'] ?? null);
        if ($box === null || $box === '') Response::error('Box required', 422);
        $hasNoLaci = ($laci === null || $laci === '');
        if (is_string($box) && strpos($box, '-') !== false && $hasNoLaci) {
            $parts = explode('-', $box);
            $box = trim($parts[0] ?? $box);
            $laci = isset($parts[1]) ? trim($parts[1]) : null;
        }
        Response::json(['data' => Location::productsByLocation((string)$box, $laci !== null ? (string)$laci : null)]);
    }

    public function store(): void {
        // Virtual creation
        Response::json(['message' => 'Location is virtual based on box/laci. Create a product with that box/laci to create location.', 'data' => Location::getBoxes()], 201);
    }

    public function update(array $params): void {
        $id = $this->decodeParam($params['id'] ?? '');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) Response::error('Invalid JSON', 400);
        if (isset($input['nomor_box'])) $input['nomor_box'] = trim((string)$input['nomor_box']);
        if (isset($input['nomor_laci'])) $input['nomor_laci'] = trim((string)$input['nomor_laci']);
        try {
            Location::update($id, $input);
            $newBox = $input['nomor_box'] ?? null;
            $newLaci = $input['nomor_laci'] ?? null;
            $lookupId = ($newBox !== null && $newLaci !== null && $newBox !== '' && $newLaci !== '') ? ($newBox . '-' . $newLaci) : $id;
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
        $id = $this->decodeParam($params['id'] ?? '');
        if ($id === '' ) Response::error('ID required', 422);
        try {
            Location::delete($id);
            Response::json(['message' => 'Location deleted (no products remain)']);
        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
