<template>
  <div>
    <!-- Hidden upload inputs -->
    <input ref="quickUploadInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onQuickPhotoSelected" />
    <input ref="modalUploadInput" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" @change="onModalPhotoSelected" />
    <!-- Mobile camera capture input (works on HTTP, triggers native camera) -->
    <input ref="cameraFileInput" type="file" accept="image/*" capture="environment" class="hidden" @change="onCameraFileSelected" />

    <div class="mb-4 lg:mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">INVENTORY OVERVIEW</div>
      <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-3">
        <div>
          <h1 class="text-[24px] lg:text-[32px] font-extrabold tracking-tight leading-none mt-1">Data Barang</h1>
          <p class="text-[11px] lg:text-[12px] text-gray-500 mt-1">{{ meta.total }} jenis • Tap foto/📷 untuk buka modal • di modal langsung browse file</p>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
          <UiButton variant="secondary" @click="exportCsv" class="flex-1 lg:flex-none h-11 lg:h-10 text-[12px]">📥 Export</UiButton>
          <label class="flex-1 lg:flex-none cursor-pointer inline-flex items-center justify-center h-11 lg:h-10 px-4 rounded-[12px] bg-[#0F1E35] text-white text-[13px] font-medium">
            📤 Import
            <input type="file" accept=".csv" class="hidden" @change="onImportFile" />
          </label>
          <UiButton @click="showCreate = true" class="flex-1 lg:flex-none h-11 lg:h-10">+ Tambah</UiButton>
        </div>
      </div>
    </div>

    <Card class="mb-4 lg:mb-5">
      <CardContent class="p-3 lg:p-4 space-y-3">
        <div class="flex flex-col lg:flex-row gap-2 lg:gap-3 lg:items-center">
          <div class="flex-1 min-w-0">
            <UiInput v-model="search" @input="debouncedFetch" placeholder="Cari ID / Nama / Keterangan / Kategori / Box..." class="h-11 lg:h-10 text-[14px]" />
          </div>
          <div class="flex gap-2">
            <label class="flex items-center gap-1.5 text-[11px] bg-white border border-[#E8DDC7] rounded-[10px] px-3 h-10"><input type="checkbox" v-model="lowStockOnly" @change="resetAndFetch" /> Stok Rendah</label>
            <select v-model="perPage" @change="resetAndFetch" class="h-10 rounded-[10px] border border-[#E8DDC7] bg-white px-3 text-[12px] flex-1 lg:flex-none">
              <option :value="10">10 / hal</option>
              <option :value="20">20 / hal</option>
              <option :value="50">50 / hal</option>
              <option :value="100">100 / hal</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 lg:flex gap-2 lg:gap-3">
          <select v-model="kategoriFilter" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-[12px] lg:text-sm">
            <option value="">Semua Kategori</option>
            <option v-for="c in categories" :key="c.kategori" :value="c.kategori">{{ c.kategori }}</option>
          </select>
          <select v-model="boxFilter" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-[12px] lg:text-sm">
            <option value="">Semua Box</option>
            <option v-for="b in boxes" :key="b.nomor_box" :value="b.nomor_box">Box {{ b.nomor_box }}</option>
          </select>
        </div>
      </CardContent>
    </Card>

    <!-- Desktop Table -->
    <Card class="hidden lg:block">
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[11px]">
          <thead class="border-b border-[#E8DDC7] bg-[#FFFBF2]">
            <tr class="text-[11px] font-bold text-[#0F172A] text-left tracking-wide">
              <th class="py-3 px-3 cursor-pointer whitespace-nowrap" @click="sortBy('id')">ID ▲</th>
              <th class="py-3 whitespace-nowrap">Foto</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('nama')">Name ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('keterangan')">Keterangan ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('kategori')">Kategori ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('box')">No Box ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('laci')">No Laci ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('harga')">Harga (Rp) ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('stock')">Stock Total ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('totalValue')">Total Value (Rp) ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('stock')">Stock ↕</th>
              <th class="py-3 pr-4 whitespace-nowrap">Aksi ↕</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in sortedProducts" :key="p.id" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2] transition" :class="{'bg-red-50/40': p.stock <= p.batas_stock}">
              <td class="py-3 px-3 font-mono text-[11px]">{{ p.id }}</td>
              <td class="py-2">
                <div v-if="getPhotoUrl(p.foto)" class="w-10 h-10 rounded-[8px] overflow-hidden border border-[#E8DDC7] bg-white cursor-pointer hover:ring-2 hover:ring-[#0F1E35]/20 transition group relative" @click="openPhotoModal(p)">
                  <img :src="getPhotoUrl(p.foto)" :alt="p.nama" class="w-full h-full object-cover" loading="lazy" @error="onImgError" />
                  <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"><span class="text-white text-[10px] font-bold">👁️</span></div>
                </div>
                <div v-else class="w-10 h-10 rounded-[8px] border border-dashed border-[#E8DDC7] bg-[#FFFBF2] flex items-center justify-center text-[14px] cursor-pointer hover:bg-white hover:border-[#0F1E35] transition" @click="openPhotoModal(p)" title="Lihat modal foto, lalu pilih file">📷</div>
              </td>
              <td class="font-semibold max-w-[180px] truncate" :title="p.nama">{{ p.nama }}</td>
              <td class="max-w-[200px] truncate text-gray-600" :title="p.keterangan_barang">{{ p.keterangan_barang }}</td>
              <td><span class="px-2 py-1 bg-[#DBEAFE] text-[#1E40AF] rounded-full text-[10px] font-bold">{{ p.kategori }}</span></td>
              <td class="text-center">{{ p.nomor_box }}</td>
              <td class="text-center">{{ p.nomor_laci }}</td>
              <td class="font-mono text-[11px]">Rp{{ Number(p.harga).toLocaleString('id-ID') }}</td>
              <td class="font-bold text-center">{{ p.stock }}</td>
              <td class="font-mono font-bold text-[#0F1E35]">{{ Number(p.harga * p.stock).toLocaleString('id-ID') }}</td>
              <td class="py-2">
                <div class="flex flex-col gap-1 items-start">
                  <div class="text-[10px] text-gray-500">Stok: <span :class="p.stock <= p.batas_stock ? 'text-red-600 font-bold' : 'font-bold text-[#0F172A]'">{{ p.stock }}</span>/{{ p.batas_stock }}</div>
                  <div class="flex gap-1">
                    <button @click="quickStock(p, -1)" :disabled="stockChanging[p.id] || p.stock<=0" class="w-[46px] h-[26px] bg-white border border-[#E8DDC7] hover:bg-[#F3EBD9] disabled:opacity-40 text-[#0F1E35] rounded-[8px] text-[11px] font-bold flex items-center justify-center">− 1</button>
                    <button @click="quickStock(p, 1)" :disabled="stockChanging[p.id]" class="w-[46px] h-[26px] bg-[#0F1E35] hover:bg-[#162a4a] disabled:opacity-40 text-white rounded-[8px] text-[11px] font-bold flex items-center justify-center">+ 1</button>
                  </div>
                </div>
              </td>
              <td class="py-2 pr-3">
                <div class="flex flex-col gap-1">
                  <button @click="editProduct(p)" class="w-[52px] h-[28px] bg-white border border-[#E8DDC7] hover:bg-[#F3EBD9] text-[#0F1E35] rounded-[8px] flex items-center justify-center" title="Edit">✏️</button>
                  <button @click="deleteProduct(p.id)" class="w-[52px] h-[28px] bg-white border border-[#E8DDC7] hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-[#0F1E35] rounded-[8px] flex items-center justify-center" title="Hapus">🗑️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="products.length===0" class="py-12 text-center text-sm text-gray-500">Tidak ada data</div>
      </CardContent>
    </Card>

    <!-- Mobile Cards -->
    <div class="lg:hidden space-y-3">
      <div v-for="p in sortedProducts" :key="p.id" class="bg-white rounded-[16px] border border-[#F0E6D2] shadow-[0_2px_12px_rgba(0,0,0,0.03)] p-3 flex gap-3" :class="{'border-red-200 bg-red-50/30': p.stock <= p.batas_stock}">
        <div class="flex-shrink-0">
          <div v-if="getPhotoUrl(p.foto)" class="w-[64px] h-[64px] rounded-[12px] overflow-hidden border border-[#E8DDC7] bg-[#FFFBF2] cursor-pointer" @click="openPhotoModal(p)">
            <img :src="getPhotoUrl(p.foto)" :alt="p.nama" class="w-full h-full object-cover" loading="lazy" @error="onImgError" />
          </div>
          <div v-else class="w-[64px] h-[64px] rounded-[12px] border border-dashed border-[#E8DDC7] bg-[#FFFBF2] flex items-center justify-center text-[20px] cursor-pointer active:bg-white" @click="openPhotoModal(p)" title="Tap untuk lihat modal foto">📷</div>
          <div class="mt-2 text-center">
            <div class="text-[10px] font-mono text-gray-500">#{{ p.id }}</div>
            <div class="text-[10px] font-bold px-1.5 py-0.5 bg-[#DBEAFE] text-[#1E40AF] rounded-full inline-block mt-1">{{ p.kategori || '?' }}</div>
          </div>
        </div>
        <div class="flex-1 min-w-0">
          <div class="font-bold text-[13px] leading-tight truncate" :title="p.nama">{{ p.nama }}</div>
          <div class="text-[11px] text-gray-600 line-clamp-2 mt-0.5">{{ p.keterangan_barang }}</div>
          <div class="mt-2 flex flex-wrap gap-1.5 text-[10px]">
            <span class="px-2 py-1 bg-[#FFFBF2] border border-[#F0E6D2] rounded-full">Box {{ p.nomor_box }} • Laci {{ p.nomor_laci }}</span>
            <span class="px-2 py-1 bg-white border border-[#E8DDC7] rounded-full font-mono">Rp{{ Number(p.harga).toLocaleString('id-ID') }}</span>
          </div>
          <div class="mt-2 flex justify-between items-center">
            <div>
              <div class="text-[10px] text-gray-500">Stok: <span :class="p.stock <= p.batas_stock ? 'text-red-600 font-bold' : 'font-bold'">{{ p.stock }}</span>/{{ p.batas_stock }} • Rp{{ Number(p.harga*p.stock).toLocaleString('id-ID') }}</div>
              <div class="flex gap-2 mt-1">
                <button @click="quickStock(p, -1)" :disabled="stockChanging[p.id] || p.stock<=0" class="h-8 px-3 bg-white border border-[#E8DDC7] rounded-[8px] text-[12px] font-bold disabled:opacity-40">−1</button>
                <button @click="quickStock(p, 1)" :disabled="stockChanging[p.id]" class="h-8 px-3 bg-[#0F1E35] text-white rounded-[8px] text-[12px] font-bold disabled:opacity-40">+1</button>
              </div>
            </div>
            <div class="flex gap-1.5">
              <button @click="editProduct(p)" class="w-9 h-9 bg-[#FFFBF2] border border-[#E8DDC7] rounded-[10px] flex items-center justify-center text-[14px]">✏️</button>
              <button @click="deleteProduct(p.id)" class="w-9 h-9 bg-white border border-[#E8DDC7] rounded-[10px] flex items-center justify-center text-[14px]">🗑️</button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="products.length===0" class="py-12 text-center text-sm text-gray-500 bg-white rounded-[16px] border border-[#F0E6D2]">Tidak ada data</div>
    </div>

    <div class="flex flex-col lg:flex-row justify-between items-center mt-4 gap-2">
      <div class="text-[11px] text-gray-500 order-2 lg:order-1">{{ products.length }} dari {{ meta.total }} • Hal {{ meta.page }}/{{ meta.total_pages }}</div>
      <div class="flex gap-1 order-1 lg:order-2 w-full lg:w-auto justify-center">
        <UiButton variant="secondary" size="sm" @click="goPage(1)" :disabled="meta.page<=1" class="h-9 flex-1 lg:flex-none">«</UiButton>
        <UiButton variant="secondary" size="sm" @click="goPage(meta.page-1)" :disabled="meta.page<=1" class="h-9 flex-1 lg:flex-none">‹ Prev</UiButton>
        <span class="px-3 py-1 bg-[#0F1E35] text-white rounded-[8px] text-[11px] flex items-center">{{ meta.page }}/{{ meta.total_pages }}</span>
        <UiButton variant="secondary" size="sm" @click="goPage(meta.page+1)" :disabled="meta.page>=meta.total_pages" class="h-9 flex-1 lg:flex-none">Next ›</UiButton>
        <UiButton variant="secondary" size="sm" @click="goPage(meta.total_pages)" :disabled="meta.page>=meta.total_pages" class="h-9 flex-1 lg:flex-none">»</UiButton>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreate || editing" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-end lg:items-center justify-center p-0 lg:p-4 z-50">
      <Card class="w-full max-w-lg max-h-[92vh] lg:max-h-[90vh] overflow-auto rounded-t-[20px] lg:rounded-[20px] border-t-0 lg:border">
        <CardHeader class="sticky top-0 bg-white z-10 border-b border-[#F0E6D2] lg:border-0">
          <div class="flex justify-between items-center">
            <div><CardTitle class="text-[16px] lg:text-[18px]">{{ editing ? 'Edit Barang' : 'Tambah Barang' }}</CardTitle><CardDescription class="text-[11px]">data_barang • Bahasa Indonesia</CardDescription></div>
            <button @click="closeModal" class="lg:hidden w-8 h-8 rounded-full bg-[#FFFBF2] border border-[#E8DDC7] flex items-center justify-center">✕</button>
          </div>
        </CardHeader>
        <CardContent class="p-4">
          <form @submit.prevent="saveProduct" class="space-y-3">
            <div><label class="text-[11px] font-bold">Name / Nama *</label><UiInput v-model="form.nama" placeholder="Nama barang" class="h-11 text-[14px]" /></div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
              <div><label class="text-[11px] font-bold">Kategori</label><input v-model="form.kategori" list="kat-list" placeholder="Kategori" class="h-11 rounded-[12px] border border-[var(--color-border)] px-3 text-[14px] w-full" /></div>
              <div><label class="text-[11px] font-bold">Harga (Rp)</label><UiInput v-model="form.harga" type="number" class="h-11 text-[14px]" /></div>
            </div>
            <datalist id="kat-list"><option v-for="c in categories" :key="c.kategori" :value="c.kategori" /></datalist>
            <div><label class="text-[11px] font-bold">Keterangan</label><textarea v-model="form.keterangan_barang" placeholder="Keterangan" class="w-full rounded-[12px] border border-[var(--color-border)] p-3 text-[14px] min-h-[70px]"></textarea></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="text-[11px] font-bold">No Box</label><UiInput v-model="form.nomor_box" class="h-11 text-[14px]" inputmode="numeric" /></div>
              <div><label class="text-[11px] font-bold">No Laci</label><UiInput v-model="form.nomor_laci" class="h-11 text-[14px]" inputmode="numeric" /></div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-3">
              <div><label class="text-[11px] font-bold">Stock Total</label><UiInput v-model="form.stock" type="number" class="h-11 text-[14px]" /></div>
              <div><label class="text-[11px] font-bold">Batas Stock</label><UiInput v-model="form.batas_stock" type="number" class="h-11 text-[14px]" /></div>
              <div class="col-span-2 lg:col-span-1"><label class="text-[11px] font-bold">ID</label><input :value="editing?editing.id:'auto'" disabled class="h-11 rounded-[12px] border bg-[#FFFBF2] px-3 text-sm w-full" /></div>
            </div>

            <div class="border border-[var(--color-border)] rounded-[12px] p-3 bg-[#FFFBF2]/50">
              <label class="text-[11px] font-bold block mb-2">Foto Produk (JPG/PNG/WEBP max 5MB)</label>
              <div class="flex gap-3 items-start">
                <div class="w-[96px] h-[96px] rounded-[12px] border border-[#E8DDC7] bg-white overflow-hidden flex items-center justify-center flex-shrink-0">
                  <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                  <span v-else class="text-gray-400 text-2xl">📷</span>
                </div>
                <div class="flex-1 space-y-2 min-w-0">
                  <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="onPhotoSelect" class="block w-full text-[11px] text-gray-600 file:mr-2 file:py-2 file:px-3 file:rounded-[8px] file:border file:border-[#E8DDC7] file:bg-white file:text-[11px] file:font-bold hover:file:bg-[#F3EBD9]" />
                  <div class="flex flex-wrap gap-2">
                    <button v-if="editing && editing.foto" type="button" @click="deleteExistingPhoto" class="text-[10px] px-2 py-1.5 bg-white border border-red-200 text-red-600 rounded-[6px] hover:bg-red-50 h-7">Hapus foto tersimpan</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 sticky bottom-0 bg-white py-2 lg:static">
              <UiButton variant="secondary" @click="closeModal" type="button" class="h-11 flex-1 lg:flex-none">Batal</UiButton>
              <UiButton type="submit" :disabled="saving" class="h-11 flex-1 lg:flex-none">{{ saving ? 'Menyimpan...' : 'Simpan' }}</UiButton>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- Stock adjust modal -->
    <div v-if="stockProduct" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-end lg:items-center justify-center p-0 lg:p-4 z-50">
      <Card class="w-full max-w-md rounded-t-[20px] lg:rounded-[20px] max-h-[90vh] overflow-auto">
        <CardHeader><CardTitle>Adjust Stock: {{ stockProduct.nama }}</CardTitle><CardDescription>No Box {{ stockProduct.nomor_box }} No Laci {{ stockProduct.nomor_laci }} • Stock {{ stockProduct.stock }}</CardDescription></CardHeader>
        <CardContent>
          <form @submit.prevent="submitStock" class="space-y-3">
            <select v-model="stockForm.type" class="h-11 w-full rounded-[12px] border border-[var(--color-border)] px-3 text-[14px]">
              <option value="in">IN (+) tambah</option><option value="out">OUT (-) pakai</option><option value="adjustment">Adjustment</option>
            </select>
            <UiInput v-model="stockForm.quantity" type="number" placeholder="Qty" class="h-11 text-[14px]" />
            <UiInput v-model="stockForm.reason" placeholder="Alasan" class="h-11 text-[14px]" />
            <div class="flex justify-end gap-2 sticky bottom-0 bg-white py-2"><UiButton variant="secondary" @click="stockProduct=null" type="button" class="h-11 flex-1">Batal</UiButton><UiButton type="submit" class="h-11 flex-1">Update</UiButton></div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- Photo Modal – direct file browse -->
    <div v-if="photoModalProduct" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-end lg:items-center justify-center p-0 lg:p-4 z-[60]" @click.self="closePhotoModal">
      <Card class="w-full max-w-3xl max-h-[92vh] lg:max-h-[90vh] overflow-auto bg-white rounded-t-[20px] lg:rounded-[20px]">
        <CardHeader class="flex flex-row justify-between items-start gap-3 sticky top-0 bg-white z-10 border-b border-[#F0E6D2] lg:border-0">
          <div class="min-w-0 flex-1">
            <CardTitle class="text-[14px] lg:text-[16px] truncate">{{ photoModalProduct.nama }}</CardTitle>
            <CardDescription class="text-[11px]">ID {{ photoModalProduct.id }} • Box {{ photoModalProduct.nomor_box }} Laci {{ photoModalProduct.nomor_laci }} • {{ photoModalProduct.kategori }}</CardDescription>
          </div>
          <div class="flex gap-2 flex-shrink-0">
            <UiButton variant="secondary" size="sm" @click="closePhotoModal" class="h-9">✕</UiButton>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <!-- Main photo view -->
          <div v-if="!cameraActive && !capturedPreview">
            <div v-if="getPhotoUrl(photoModalProduct.foto)" class="bg-[#FAF6EE] flex items-center justify-center p-3 lg:p-6 min-h-[260px]">
              <img :src="getPhotoUrl(photoModalProduct.foto)" :alt="photoModalProduct.nama" class="max-w-full max-h-[60vh] lg:max-h-[70vh] object-contain rounded-[12px] shadow-sm" />
            </div>
            <div v-else class="py-16 lg:py-20 text-center bg-[#FFFBF2]">
              <div class="text-5xl mb-3">📷</div>
              <div class="text-[14px] font-bold">Tidak ada foto</div>
              <div class="text-[11px] text-gray-500 mt-1">Gunakan tombol di bawah untuk ambil foto via kamera atau pilih file</div>
            </div>
          </div>

          <!-- Camera active view -->
          <div v-if="cameraActive" class="bg-black flex flex-col items-center justify-center p-3 lg:p-4 space-y-3">
            <div class="relative w-full max-w-[480px] aspect-[4/3] bg-black rounded-[12px] overflow-hidden border border-[#333]">
              <video ref="cameraVideo" autoplay playsinline muted class="w-full h-full object-cover"></video>
              <div class="absolute bottom-2 left-2 text-[10px] bg-black/60 text-white px-2 py-1 rounded-full">Kamera aktif • {{ cameraFacing === 'environment' ? 'Belakang' : 'Depan' }}</div>
            </div>
            <canvas ref="cameraCanvas" class="hidden"></canvas>
            <div v-if="cameraError" class="text-[11px] text-red-400 bg-red-950/50 border border-red-800 rounded-[8px] px-3 py-2 max-w-[480px] w-full">{{ cameraError }}</div>
            <div class="flex gap-2 w-full max-w-[480px]">
              <UiButton size="sm" @click="capturePhoto" class="h-10 flex-1 bg-white text-black hover:bg-gray-100 border-white">📸 Ambil Foto</UiButton>
              <UiButton variant="secondary" size="sm" @click="switchCamera" class="h-10 px-3" title="Ganti kamera">🔄</UiButton>
              <UiButton variant="secondary" size="sm" @click="closeCamera" class="h-10 px-3">✕ Tutup</UiButton>
            </div>
          </div>

          <!-- Captured preview -->
          <div v-if="capturedPreview" class="bg-[#FAF6EE] flex flex-col items-center justify-center p-3 lg:p-6 space-y-3 min-h-[260px]">
            <div class="text-[11px] font-bold text-[#0F1E35]">Preview hasil kamera</div>
            <img :src="capturedPreview" class="max-w-full max-h-[60vh] lg:max-h-[70vh] object-contain rounded-[12px] shadow-sm border border-[#E8DDC7]" />
            <div class="flex gap-2 w-full max-w-[480px] justify-center">
              <UiButton size="sm" @click="uploadCapturedPhoto" class="h-10 flex-1" :disabled="uploadingPhoto">{{ uploadingPhoto ? 'Uploading...' : '✅ Upload hasil kamera' }}</UiButton>
              <UiButton variant="secondary" size="sm" @click="retakePhoto" class="h-10 flex-1">🔄 Ulangi</UiButton>
            </div>
          </div>

          <div class="p-4 border-t border-[#F0E6D2] bg-white space-y-3">
            <div v-if="cameraError" class="text-[11px] text-amber-800 bg-amber-50 border border-amber-200 rounded-[8px] px-3 py-2">{{ cameraError }}</div>
            <div class="flex flex-wrap gap-2">
              <UiButton size="sm" @click="triggerModalUpload" class="h-10 flex-1 lg:flex-none" :disabled="uploadingPhoto || cameraActive">
                {{ uploadingPhoto ? 'Uploading...' : (photoModalProduct.foto ? '🔄 Ganti Foto' : '📤 Pilih File') }}
              </UiButton>
              <UiButton size="sm" @click="openCamera" class="h-10 flex-1 lg:flex-none bg-[#0F1E35] text-white hover:bg-[#162a4a]" :disabled="uploadingPhoto || cameraActive">
                📷 Buka kamera
              </UiButton>
              <UiButton size="sm" @click="triggerCameraFileInput" class="h-10 flex-1 lg:flex-none bg-white border border-[#E8DDC7] text-[#0F1E35]" :disabled="uploadingPhoto || cameraActive">
                📱 Kamera HP
              </UiButton>
              <UiButton v-if="photoModalProduct.foto" variant="secondary" size="sm" @click="deletePhotoFromModal" class="h-10 flex-1 lg:flex-none" :disabled="cameraActive">🗑️ Hapus foto</UiButton>
              <UiButton variant="secondary" size="sm" @click="closePhotoModal" class="h-10 flex-1 lg:flex-none">Tutup</UiButton>
            </div>
            <div class="text-[10px] text-gray-500 leading-tight">Pilih File = browse. Buka kamera = webcam (butuh HTTPS/localhost). Kamera HP = native camera input (works di HTTP / mobile).</div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 text-[11px] mt-2">
              <div class="bg-white lg:bg-[#FFFBF2] rounded-[10px] px-3 py-2.5 border border-[#E8DDC7] lg:border-[#F0E6D2] shadow-sm lg:shadow-none">
                <div class="text-[9px] uppercase tracking-wide text-gray-500 font-bold">Harga Satuan</div>
                <div class="font-mono font-extrabold text-[13px] text-[#0F1E35] mt-0.5">Rp{{ Number(photoModalProduct.harga).toLocaleString('id-ID') }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5">per pcs</div>
              </div>
              <div class="bg-[#FFFBF2] rounded-[10px] px-3 py-2.5 border border-[#F0E6D2]">
                <div class="text-[9px] uppercase tracking-wide text-gray-500 font-bold">Stock</div>
                <div class="font-bold text-[13px] mt-0.5">{{ photoModalProduct.stock }} <span class="text-gray-400 text-[11px]">/ {{ photoModalProduct.batas_stock }}</span></div>
                <div class="text-[10px] mt-0.5" :class="photoModalProduct.stock <= photoModalProduct.batas_stock ? 'text-red-600 font-bold' : 'text-gray-500'">{{ photoModalProduct.stock <= photoModalProduct.batas_stock ? '⚠️ Rendah' : '✅ Aman' }}</div>
              </div>
              <div class="bg-[#FFFBF2] rounded-[10px] px-3 py-2.5 border border-[#F0E6D2]">
                <div class="text-[9px] uppercase tracking-wide text-gray-500 font-bold">Lokasi</div>
                <div class="font-bold text-[12px] mt-0.5">Box {{ photoModalProduct.nomor_box }} • Laci {{ photoModalProduct.nomor_laci }}</div>
                <div class="text-[10px] text-gray-500 mt-0.5 truncate">{{ photoModalProduct.kategori }}</div>
              </div>
              <div class="bg-[#0F1E35] rounded-[10px] px-3 py-2.5 border border-[#0F1E35] text-white">
                <div class="text-[9px] uppercase tracking-wide text-white/60 font-bold">Total Value</div>
                <div class="font-mono font-extrabold text-[13px] mt-0.5">Rp{{ Number(photoModalProduct.harga * photoModalProduct.stock).toLocaleString('id-ID') }}</div>
                <div class="text-[10px] text-white/60 mt-0.5">{{ photoModalProduct.stock }} × Rp{{ Number(photoModalProduct.harga).toLocaleString('id-ID') }}</div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue'
import { productService, categoryService, locationService, getPhotoUrl } from '../services/api'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardDescription from '../components/ui/CardDescription.vue'
import CardContent from '../components/ui/CardContent.vue'
import UiButton from '../components/ui/Button.vue'
import UiInput from '../components/ui/Input.vue'

const products = ref([])
const categories = ref([])
const boxes = ref([])
const search = ref('')
const kategoriFilter = ref('')
const boxFilter = ref('')
const lowStockOnly = ref(false)
const showCreate = ref(false)
const editing = ref(null)
const stockProduct = ref(null)
const perPage = ref(20)
const meta = ref({ total:0, page:1, per_page:20, total_pages:1 })
const stockChanging = reactive({})
const sortKey = ref('id')
const sortAsc = ref(true)
const saving = ref(false)
const photoFile = ref(null)
const photoPreview = ref(null)
const photoModalProduct = ref(null)
const uploadingPhoto = ref(false)

// Direct quick upload refs
const quickUploadInput = ref(null)
const modalUploadInput = ref(null)
const cameraFileInput = ref(null)
const quickPhotoProductId = ref(null)

// Camera refs and state
const cameraVideo = ref(null)
const cameraCanvas = ref(null)
const cameraActive = ref(false)
const cameraError = ref('')
const cameraFacing = ref('environment') // environment or user
const capturedPreview = ref(null)
const capturedBlob = ref(null)
let cameraStream = null

const form = reactive({ nama:'', kategori:'', keterangan_barang:'', nomor_box:'', nomor_laci:'', harga:0, stock:0, batas_stock:10 })
const stockForm = reactive({ type:'in', quantity:1, reason:'' })

let timer=null
const debouncedFetch = ()=>{ clearTimeout(timer); timer=setTimeout(()=>{meta.value.page=1; fetchProducts()},350) }
const resetAndFetch = ()=>{ meta.value.page=1; fetchProducts() }

const fetchProducts = async () => {
  const params = {
    page: meta.value.page,
    per_page: perPage.value,
    search: search.value||undefined,
    kategori: kategoriFilter.value||undefined,
    nomor_box: boxFilter.value||undefined,
    low_stock: lowStockOnly.value?1:undefined,
    sort_by: sortKey.value||undefined,
    sort_dir: sortAsc.value ? 'asc' : 'desc'
  }
  try {
    const res = await productService.getAll(params)
    products.value = res.data.data
    if(res.data.meta) meta.value = res.data.meta
  } catch(e) { console.error(e) }
}
const fetchMeta = async () => {
  try {
    const [catRes, boxRes] = await Promise.all([categoryService.getAll(), locationService.getBoxes()])
    categories.value = catRes.data.data
    boxes.value = boxRes.data.data
  } catch(e) { console.error(e) }
}
onMounted(()=>{fetchProducts(); fetchMeta()})

const sortedProducts = computed(()=>{
  let list = [...products.value]
  if (list.length > perPage.value) return list
  if (sortKey.value==='totalValue' || sortKey.value==='total_value') {
    list.sort((a,b)=> sortAsc.value ? (a.harga*a.stock)-(b.harga*b.stock) : (b.harga*b.stock)-(a.harga*a.stock))
  } else if (sortKey.value==='stock') {
    list.sort((a,b)=> sortAsc.value ? a.stock-b.stock : b.stock-a.stock)
  } else if (sortKey.value==='harga') {
    list.sort((a,b)=> sortAsc.value ? a.harga-b.harga : b.harga-a.harga)
  } else if (sortKey.value==='id') {
    list.sort((a,b)=> sortAsc.value ? a.id-b.id : b.id-a.id)
  } else if (sortKey.value==='nama') {
    list.sort((a,b)=> sortAsc.value ? a.nama.localeCompare(b.nama) : b.nama.localeCompare(a.nama))
  } else if (sortKey.value==='keterangan') {
    list.sort((a,b)=> sortAsc.value ? (a.keterangan_barang||'').localeCompare(b.keterangan_barang||'') : (b.keterangan_barang||'').localeCompare(a.keterangan_barang||''))
  } else if (sortKey.value==='kategori') {
    list.sort((a,b)=> sortAsc.value ? (a.kategori||'').localeCompare(b.kategori||'') : (b.kategori||'').localeCompare(a.kategori||''))
  } else if (sortKey.value==='box') {
    list.sort((a,b)=> sortAsc.value ? parseInt(a.nomor_box||0)-parseInt(b.nomor_box||0) : parseInt(b.nomor_box||0)-parseInt(a.nomor_box||0))
  } else if (sortKey.value==='laci') {
    list.sort((a,b)=> sortAsc.value ? parseInt(a.nomor_laci||0)-parseInt(b.nomor_laci||0) : parseInt(b.nomor_laci||0)-parseInt(a.nomor_laci||0))
  }
  return list
})
const sortBy = (key) => {
  if (sortKey.value===key) sortAsc.value=!sortAsc.value
  else { sortKey.value=key; sortAsc.value=true }
  fetchProducts()
}
const visiblePages = computed(()=>{
  const total=meta.value.total_pages, cur=meta.value.page
  if(total<=7) return Array.from({length:total},(_,i)=>i+1)
  const pages=[1]; if(cur>3) pages.push('...'); for(let i=Math.max(2,cur-1); i<=Math.min(total-1,cur+1); i++) pages.push(i); if(cur<total-2) pages.push('...'); pages.push(total); return pages
})
const goPage = (p)=>{ if(p<1||p>meta.value.total_pages) return; meta.value.page=p; fetchProducts(); window.scrollTo({top:0,behavior:'smooth'}) }
const clearPhoto = () => { photoFile.value=null; photoPreview.value=null }
const onPhotoSelect = (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > 5*1024*1024) { alert('File terlalu besar max 5MB'); e.target.value=''; return }
  photoFile.value = file
  const reader = new FileReader()
  reader.onload = (ev) => { photoPreview.value = ev.target.result }
  reader.readAsDataURL(file)
}
const closeModal = ()=>{
  showCreate.value=false; editing.value=null
  Object.assign(form,{nama:'',kategori:'',keterangan_barang:'',nomor_box:'',nomor_laci:'',harga:0,stock:0,batas_stock:10})
  clearPhoto()
}
const saveProduct = async () => {
  if (saving.value) return
  saving.value=true
  try{
    let savedId = editing.value?.id
    if(editing.value) {
      await productService.update(editing.value.id,form)
      savedId = editing.value.id
    } else {
      const res = await productService.create(form)
      savedId = res.data.data.id
    }
    if (photoFile.value && savedId) {
      try { await productService.uploadPhoto(savedId, photoFile.value) } catch(e) { alert('Barang tersimpan tapi foto gagal upload: ' + (e.response?.data?.error||e.message)) }
    }
    closeModal(); fetchProducts(); fetchMeta()
  } catch(e){ alert(e.response?.data?.error||e.message) }
  finally { saving.value=false }
}
const editProduct = (p)=>{
  editing.value=p
  Object.assign(form,{nama:p.nama,kategori:p.kategori,keterangan_barang:p.keterangan_barang,nomor_box:p.nomor_box,nomor_laci:p.nomor_laci,harga:p.harga,stock:p.stock,batas_stock:p.batas_stock})
  photoFile.value=null; photoPreview.value = p.foto ? getPhotoUrl(p.foto) : null
}
const deleteProduct = async (id)=>{ if(!confirm('Hapus barang ini?')) return; try { await productService.delete(id); fetchProducts() } catch(e){ alert(e.response?.data?.error||e.message) } }
const deleteExistingPhoto = async () => {
  if (!editing.value?.id) return
  if (!confirm('Hapus foto tersimpan?')) return
  try { await productService.deletePhoto(editing.value.id); alert('Foto dihapus'); editing.value.foto=null; photoPreview.value=null; fetchProducts() } catch(e){ alert(e.response?.data?.error||e.message) }
}
const openStockModal = async (p)=>{ const res=await productService.getOne(p.id); stockProduct.value=res.data.data; stockForm.quantity=1; stockForm.type='in'; stockForm.reason='' }
const submitStock = async ()=>{ try{ await productService.adjustStock(stockProduct.value.id,stockForm); stockProduct.value=null; fetchProducts() } catch(e){ alert(e.response?.data?.error||e.message) } }
const quickStock = async (product, delta) => {
  if (stockChanging[product.id]) return
  if (delta<0 && product.stock<=0) { alert('Stock sudah 0'); return }
  stockChanging[product.id]=true
  try{
    const type = delta>0 ? 'in' : 'out'
    await productService.adjustStock(product.id, { type, quantity: Math.abs(delta), reason: `Quick ${delta>0?'+':''}${delta} table` })
    product.stock += delta
  } catch(e){ alert(e.response?.data?.error||e.message) }
  finally{ stockChanging[product.id]=false; fetchProducts() }
}
const exportCsv = async () => {
  const params = { search: search.value||undefined, kategori: kategoriFilter.value||undefined, nomor_box: boxFilter.value||undefined, low_stock: lowStockOnly.value?1:undefined }
  const res = await productService.exportCsv(params)
  const blob = new Blob([res.data], {type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=`inventory_${new Date().toISOString().slice(0,10)}.csv`; a.click(); URL.revokeObjectURL(url)
}
const onImportFile = async (e) => {
  const file=e.target.files[0]; if(!file) return
  if(!confirm(`Import ${file.name}?`)) return
  try{ const res=await productService.importCsv(file); alert(res.data.message); fetchProducts() } catch(err){ alert(err.response?.data?.error||err.message) }
  e.target.value=''
}

// Direct browse logic
const triggerQuickUpload = (p) => {
  quickPhotoProductId.value = p.id
  if (quickUploadInput.value) quickUploadInput.value.click()
}
const onQuickPhotoSelected = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  const id = quickPhotoProductId.value
  if (!id) return
  if (file.size > 5*1024*1024) { alert('File terlalu besar max 5MB'); e.target.value=''; return }
  uploadingPhoto.value = true
  try {
    await productService.uploadPhoto(id, file)
    alert('Foto berhasil di-upload')
    fetchProducts()
  } catch(err) { alert(err.response?.data?.error||err.message) }
  finally { uploadingPhoto.value=false; e.target.value=''; quickPhotoProductId.value=null }
}

const openPhotoModal = (p) => {
  photoModalProduct.value = p
  // Reset camera state when opening modal
  capturedPreview.value = null
  capturedBlob.value = null
  cameraActive.value = false
  cameraError.value = ''
  if (cameraStream) {
    cameraStream.getTracks().forEach(t=>t.stop())
    cameraStream = null
  }
}
const closePhotoModal = () => {
  // Cleanup camera
  if (cameraStream) {
    cameraStream.getTracks().forEach(t=>t.stop())
    cameraStream = null
  }
  cameraActive.value = false
  cameraError.value = ''
  capturedPreview.value = null
  capturedBlob.value = null
  photoModalProduct.value = null
}
const triggerModalUpload = () => {
  if (modalUploadInput.value) modalUploadInput.value.click()
}
const onModalPhotoSelected = async (e) => {
  const file = e.target.files[0]
  if (!file || !photoModalProduct.value) return
  if (file.size > 5*1024*1024) { alert('File terlalu besar max 5MB'); e.target.value=''; return }
  uploadingPhoto.value = true
  try {
    const res = await productService.uploadPhoto(photoModalProduct.value.id, file)
    alert('Foto berhasil di-upload')
    photoModalProduct.value = res.data.data
    fetchProducts()
  } catch(err) { alert(err.response?.data?.error||err.message) }
  finally { uploadingPhoto.value=false; e.target.value='' }
}
const deletePhotoFromModal = async () => {
  if (!photoModalProduct.value) return
  if (!confirm('Hapus foto ini?')) return
  try { await productService.deletePhoto(photoModalProduct.value.id); alert('Foto dihapus'); photoModalProduct.value.foto=null; fetchProducts(); closePhotoModal() } catch(e){ alert(e.response?.data?.error||e.message) }
}

// Camera logic - robust for localhost HTTPS requirement
const openCamera = async () => {
  cameraError.value = ''
  capturedPreview.value = null
  capturedBlob.value = null

  // Check secure context - getUserMedia requires HTTPS or localhost, not 192.168.x on http
  const isSecure = window.isSecureContext
  const isLocalhost = ['localhost','127.0.0.1'].includes(window.location.hostname)
  if (!isSecure && !isLocalhost) {
    // HTTP LAN – getUserMedia blocked by browser. Auto fallback to native camera input which works on HTTP
    cameraError.value = `Anda di ${window.location.hostname} via HTTP. Browser blokir webcam (butuh HTTPS). Membuka Kamera HP native...`
    // Directly open native file picker with capture
    if (cameraFileInput.value) {
      cameraFileInput.value.click()
    }
    return
  }

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    cameraError.value = 'Browser tidak support webcam. Membuka Kamera HP native...'
    if (cameraFileInput.value) cameraFileInput.value.click()
    return
  }

  cameraActive.value = true
  await nextTick()
  await new Promise(r=>setTimeout(r,150))

  try {
    if (cameraStream) {
      cameraStream.getTracks().forEach(t=>t.stop())
      cameraStream = null
    }
    // Try with facingMode first, fallback without if fails
    let stream
    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: cameraFacing.value, width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false
      })
    } catch(e) {
      console.warn('facingMode failed, trying generic', e)
      stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false })
    }
    cameraStream = stream
    if (cameraVideo.value) {
      cameraVideo.value.srcObject = stream
      // Explicit muted and playsinline required for iOS
      cameraVideo.value.muted = true
      cameraVideo.value.playsInline = true
      try {
        await cameraVideo.value.play()
      } catch(playErr) {
        console.warn('video play failed, but stream set', playErr)
      }
    }
  } catch(err) {
    console.error('Camera error', err)
    let msg = err.message || err.name
    if (err.name === 'NotAllowedError') msg = 'Izin kamera ditolak. Cek pengaturan browser.'
    if (err.name === 'NotFoundError') msg = 'Kamera tidak ditemukan.'
    if (err.name === 'NotSecureError' || err.name === 'SecurityError') msg = 'Butuh HTTPS - fallback ke Kamera HP.'
    cameraError.value = 'Gagal akses kamera: ' + msg + '. Membuka Kamera HP native...'
    cameraActive.value = false
    if (cameraStream) {
      cameraStream.getTracks().forEach(t=>t.stop())
      cameraStream = null
    }
    // Auto fallback to native input which works on HTTP
    if (cameraFileInput.value) {
      setTimeout(()=>cameraFileInput.value.click(), 300)
    }
  }
}

const closeCamera = () => {
  if (cameraStream) {
    cameraStream.getTracks().forEach(t=>t.stop())
    cameraStream = null
  }
  cameraActive.value = false
  // don't clear error so user sees why it failed
}

const switchCamera = async () => {
  cameraFacing.value = cameraFacing.value === 'environment' ? 'user' : 'environment'
  if (cameraActive.value) {
    await openCamera()
  }
}

const capturePhoto = () => {
  if (!cameraVideo.value || !cameraCanvas.value) {
    cameraError.value = 'Video belum siap'
    return
  }
  const video = cameraVideo.value
  const canvas = cameraCanvas.value
  const w = video.videoWidth || 640
  const h = video.videoHeight || 480
  if (w === 0 || h === 0) {
    cameraError.value = 'Video belum ready, coba lagi'
    return
  }
  canvas.width = w
  canvas.height = h
  const ctx = canvas.getContext('2d')
  // Mirror for user facing? keep simple
  if (cameraFacing.value === 'user') {
    ctx.translate(w, 0)
    ctx.scale(-1, 1)
  }
  ctx.drawImage(video, 0, 0, w, h)
  canvas.toBlob((blob) => {
    if (!blob) {
      cameraError.value = 'Gagal mengambil foto'
      return
    }
    if (capturedPreview.value) URL.revokeObjectURL(capturedPreview.value)
    capturedBlob.value = blob
    capturedPreview.value = URL.createObjectURL(blob)
    closeCamera()
  }, 'image/jpeg', 0.92)
}

const retakePhoto = () => {
  if (capturedPreview.value) URL.revokeObjectURL(capturedPreview.value)
  capturedPreview.value = null
  capturedBlob.value = null
  openCamera()
}

const uploadCapturedPhoto = async () => {
  if (!capturedBlob.value || !photoModalProduct.value) return
  if (capturedBlob.value.size > 5*1024*1024) { alert('Hasil foto terlalu besar >5MB'); return }
  uploadingPhoto.value = true
  try {
    const file = new File([capturedBlob.value], `camera_${photoModalProduct.value.id}_${Date.now()}.jpg`, { type: 'image/jpeg' })
    const res = await productService.uploadPhoto(photoModalProduct.value.id, file)
    alert('Foto dari kamera berhasil di-upload')
    photoModalProduct.value = res.data.data
    if (capturedPreview.value) URL.revokeObjectURL(capturedPreview.value)
    capturedPreview.value = null
    capturedBlob.value = null
    fetchProducts()
  } catch(err) {
    alert(err.response?.data?.error || err.message)
  } finally {
    uploadingPhoto.value = false
  }
}

const triggerCameraFileInput = () => {
  if (cameraFileInput.value) cameraFileInput.value.click()
}

const onCameraFileSelected = async (e) => {
  const file = e.target.files[0]
  if (!file) return
  if (file.size > 5*1024*1024) { alert('File terlalu besar max 5MB'); e.target.value=''; return }
  // Show preview then auto upload? We'll show preview first like camera capture
  capturedBlob.value = file
  if (capturedPreview.value) URL.revokeObjectURL(capturedPreview.value)
  capturedPreview.value = URL.createObjectURL(file)
  // Optionally auto upload? Keep manual for user confirmation
  // Clear file input
  e.target.value = ''
}

const onImgError = (e) => { e.target.style.display='none' }
</script>
