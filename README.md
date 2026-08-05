# InventoryApp - PCB Expres Jogja (Native PHP + Vue + MariaDB)

Real DB: `inventory_pcbexpressjogja` ~700 items, 13k+ log_stock.
Stack: Native PHP 8.1+ Router + Vue 3 + Vite + Tailwind + MariaDB, self-hosted Docker.

## New Features (Continuation)

### 1. Pagination (Products)
- Backend: `Product::all()` now with `page`/`per_page` (5-100) + `count()` total. `ProductController::index` returns `meta {total, page, per_page, total_pages}`.
- Frontend: Products table paginated, 10/20/50/100 per page, first/prev/numbers/next/last, scroll to top on page change.
- Query params preserved: `?page=2&per_page=50&search=resistor&kategori=RESISTOR&nomor_box=1&low_stock=1`
- Selected ids persist across pages? Currently per page; bulk print uses selected ids across current page.

### 2. Barcode + QR Labels
- Deps: `jsbarcode@3.11.6` CODE128 + `qrcode@1.5.3` + `html5-qrcode@2.3.8` for camera scanning.
- Components:
  - `Barcode.vue` - SVG CODE128, format configurable, value = ID or `box-laci`
  - `QRCode.vue` - canvas QR with `PCBEXPRESS|ID:...|Box:X|Laci:Y...`
  - `LabelCard.vue` - product label detail + barcode + QR
  - `LocationLabelCard.vue` - Box-Laci large label yellow background, barcode `X-Y`
- Print view `/print`:
  - Mode: products / locations / mixed, load by IDs `?ids=4,5,10` or `?ids=1-2,1-3`
  - Paper size: A4 (3 col), 80mm thermal (1 col), 58mm small
  - Label type: detailed / small (only barcode) / boxonly
  - Adds scanned IDS via `BarcodeScanner` inside print page
  - Print CSS: `@media print` hides UI, `break-inside:avoid`, `@page margin 5mm`
  - Usage: Products → checkbox → "Cetak Label (N)" → Print page → Ctrl+P

### 3. Barcode Scanner (USB HID + Camera)
- `BarcodeScanner.vue`:
  - Input autofocus, Enter triggers scan (USB scanners act as keyboard + Enter)
  - Global key listener: if typing elsewhere, focuses scanner input (warehouse workflow)
  - Camera: HTML5Qrcode with environment camera, fps 10, qrbox 250x250, scans QR + barcode
  - Parses:
    - `123` numeric → product ID
    - `1-2` → Box 1 Laci 2 → filter products in that location
    - `PCBEXPRESS|ID:123|...` → QR from product label → open preview
    - `LOC|BOX:1|LACI:2` → QR from location label → filter + view
  - Products page: scan ID → highlight + open label preview, scan Box-Laci → filter products
  - Locations page: scan Box-Laci → auto filter + highlight + view products

### 4. CSV Export / Import
- Backend `ExportController.php`:
  - `GET /api/export/csv?search=&kategori=&nomor_box=&low_stock=` → CSV with BOM, header id,updated,nama,kategori,keterangan,box,laci,harga,stock,batas,total_value
  - `POST /api/import/csv` multipart file, upsert by ID (ON DUPLICATE KEY UPDATE), inserts missing kategori into kategori table
- Frontend: Products top bar Export CSV button downloads blob, Import CSV label with file input confirms then calls import, refreshes list

### 5. Locations Enhanced
- Before: loaded all, no search, no pagination
- Now:
  - Pagination: `perPage` 20/50/100/200, `currentPage`, `totalPages`
  - Search: Box / Laci / name / id filter, plus Box dropdown
  - `filteredLocations` computed + `pagedLocations` slice
  - Highlight on scan, `getLaciCount` per box
  - Actions: View products in location, Label print single, Preview card
  - Print all filtered (max 50) → `/print?ids=...&mode=locations`
  - Export CSV locations: box,laci,name,product_count,total_stock
  - Scanner integration via `BarcodeScanner`

### 6. Other Improvements
- `Location.php` model kept virtual but could add pagination later if needed
- `App.vue` sidebar updated with print route, feature list badges
- PrintLabels now thermal friendly with size selector, small barcode mode for 58mm printer
- Dashboard still shows stats per kategori/box

## Real DB Schema
- `data_barang`: id PK, updated, nama text, kategori text, keterangan_barang text, nomor_box varchar, nomor_laci varchar, harga int, stock int, batas_stock int
- `kategori`: kategori varchar PK
- `log_stock`: no PK, id FK data_barang, waktu timestamp, stock delta int

## API Endpoints (updated)
- `GET /api/products?page=1&per_page=20&search=&kategori=RESISTOR&nomor_box=1&low_stock=1` → {data, meta}
- `GET /api/products/{id}` with history log_stock
- `POST /api/products/{id}/stock` IN/OUT/adjustment logs delta to log_stock
- `GET /api/export/csv` + `POST /api/import/csv`
- `GET /api/locations?type=boxes|full`, `GET /api/locations/box/{box}`, `GET /api/locations/{id}` id = box-laci
- `GET /api/logs?id=` product history or recent 200
- `GET /api/print` frontend only

## Usage - Self-hosted
```bash
docker-compose up -d
# backend 8000/api/health, frontend 5173, db 3306 inventory_pcbexpressjogja
docker-compose exec backend composer install
cd frontend && npm install && npm run dev
```

## Thermal Printer Setup
- 58mm: Chrome print → More settings → Paper size 58mm x 100mm, Scale 100, Margins None, Small label mode
- 80mm: similar 80mm, Detailed mode
- A4: 3 columns grid, cut per label

## Next Ideas
- Auth + roles (admin/gudang)
- Stock opname / audit page with scanner
- Barcode generation for new items auto-print after create
- PWA offline for mobile scanning in warehouse
