<template>
  <div>
    <div class="mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">INVENTORY OVERVIEW</div>
      <div class="flex justify-between items-end flex-wrap gap-3">
        <div>
          <h1 class="text-[32px] font-extrabold tracking-tight leading-none mt-1">Data Barang</h1>
          <p class="text-[12px] text-gray-500 mt-1">Konfigurasi kolom sesuai gambar • Bahasa Indonesia • {{ meta.total }} jenis</p>
        </div>
        <div class="flex gap-2">
          <UiButton variant="secondary" @click="exportCsv">📥 Export CSV</UiButton>
          <label class="cursor-pointer inline-flex items-center justify-center h-10 px-4 rounded-[12px] bg-[#0F1E35] text-white text-sm font-medium">
            📤 Import
            <input type="file" accept=".csv" class="hidden" @change="onImportFile" />
          </label>
          <UiButton @click="showCreate = true">+ Tambah</UiButton>
        </div>
      </div>
    </div>

    <Card class="mb-5">
      <CardContent class="flex flex-wrap gap-3 items-center">
        <div class="flex-1 min-w-[220px]">
          <UiInput v-model="search" @input="debouncedFetch" placeholder="Cari ID / Nama / Keterangan / Kategori / Box..." />
        </div>
        <select v-model="kategoriFilter" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option value="">Semua Kategori</option>
          <option v-for="c in categories" :key="c.kategori" :value="c.kategori">{{ c.kategori }}</option>
        </select>
        <select v-model="boxFilter" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option value="">Semua Box</option>
          <option v-for="b in boxes" :key="b.nomor_box" :value="b.nomor_box">Box {{ b.nomor_box }}</option>
        </select>
        <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox" v-model="lowStockOnly" @change="resetAndFetch" /> Stok Rendah</label>
        <select v-model="perPage" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option :value="10">10 / hal</option>
          <option :value="20">20 / hal</option>
          <option :value="50">50 / hal</option>
          <option :value="100">100 / hal</option>
        </select>
      </CardContent>
    </Card>

    <!-- Table with Foto column -->
    <Card>
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[11px]">
          <thead class="border-b border-[#E8DDC7] bg-[#FFFBF2]">
            <tr class="text-[11px] font-bold text-[#0F172A] text-left tracking-wide">
              <th class="py-3 px-3 cursor-pointer whitespace-nowrap" @click="sortBy('id')">ID <span class="text-[#94A3B8]">▲</span></th>
              <th class="py-3 whitespace-nowrap">Foto</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('nama')">Name <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('keterangan')">Keterangan <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('kategori')">Kategori <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('box')">No Box <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('laci')">No Laci <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('harga')">Harga (Rp) <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('stock')">Stock Total <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('totalValue')">Total Stock Value (Rp) <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('stock')">Stock <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 pr-4 cursor-pointer whitespace-nowrap">Aksi <span class="text-[#E8DDC7]">↕</span></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in sortedProducts" :key="p.id" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2] transition" :class="{'bg-red-50/40': p.stock <= p.batas_stock}">
              <td class="py-3 px-3 font-mono text-[11px]">{{ p.id }}</td>
              <td class="py-2">
                <div v-if="getPhotoUrl(p.foto)" class="w-10 h-10 rounded-[8px] overflow-hidden border border-[#E8DDC7] bg-white cursor-pointer hover:ring-2 hover:ring-[#0F1E35]/20 transition" @click="openPhotoModal(p)">
                  <img :src="getPhotoUrl(p.foto)" :alt="p.nama" class="w-full h-full object-cover" loading="lazy" @error="onImgError" />
                </div>
                <div v-else class="w-10 h-10 rounded-[8px] border border-dashed border-[#E8DDC7] bg-[#FFFBF2] flex items-center justify-center text-[14px] cursor-pointer hover:bg-white transition" @click="openPhotoModal(p)" title="Tidak ada foto - klik untuk upload">
                  📷
                </div>
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
                  <button @click="editProduct(p)" class="w-[52px] h-[28px] bg-white border border-[#E8DDC7] hover:bg-[#F3EBD9] text-[#0F1E35] rounded-[8px] flex items-center justify-center" title="Edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </button>
                  <button @click="deleteProduct(p.id)" class="w-[52px] h-[28px] bg-white border border-[#E8DDC7] hover:bg-red-50 hover:text-red-600 hover:border-red-200 text-[#0F1E35] rounded-[8px] flex items-center justify-center" title="Hapus">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="products.length===0" class="py-12 text-center text-sm text-gray-500">Tidak ada data</div>
      </CardContent>
    </Card>

    <div class="flex justify-between items-center mt-4">
      <div class="text-[11px] text-gray-500">{{ products.length }} dari {{ meta.total }} • Hal {{ meta.page }}/{{ meta.total_pages }}</div>
      <div class="flex gap-1">
        <UiButton variant="secondary" size="sm" @click="goPage(1)" :disabled="meta.page<=1">«</UiButton>
        <UiButton variant="secondary" size="sm" @click="goPage(meta.page-1)" :disabled="meta.page<=1">‹</UiButton>
        <span v-for="p in visiblePages" :key="p" class="mx-0.5">
          <UiButton v-if="p!=='...'" :variant="p===meta.page?'default':'secondary'" size="sm" @click="goPage(p)" class="w-8">{{ p }}</UiButton>
          <span v-else class="px-2 text-gray-400">...</span>
        </span>
        <UiButton variant="secondary" size="sm" @click="goPage(meta.page+1)" :disabled="meta.page>=meta.total_pages">›</UiButton>
        <UiButton variant="secondary" size="sm" @click="goPage(meta.total_pages)" :disabled="meta.page>=meta.total_pages">»</UiButton>
      </div>
    </div>

    <!-- Create/Edit Modal with Photo -->
    <div v-if="showCreate || editing" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <Card class="w-full max-w-lg max-h-[90vh] overflow-auto">
        <CardHeader><CardTitle>{{ editing ? 'Edit Barang' : 'Tambah Barang' }}</CardTitle><CardDescription>data_barang • Bahasa Indonesia</CardDescription></CardHeader>
        <CardContent>
          <form @submit.prevent="saveProduct" class="space-y-3">
            <div><label class="text-[11px] font-bold">Name / Nama *</label><UiInput v-model="form.nama" placeholder="Nama barang" /></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="text-[11px] font-bold">Kategori</label><input v-model="form.kategori" list="kat-list" placeholder="Kategori" class="h-10 rounded-[12px] border border-[var(--color-border)] px-3 text-sm w-full" /></div>
              <div><label class="text-[11px] font-bold">Harga (Rp)</label><UiInput v-model="form.harga" type="number" /></div>
            </div>
            <datalist id="kat-list"><option v-for="c in categories" :key="c.kategori" :value="c.kategori" /></datalist>
            <div><label class="text-[11px] font-bold">Keterangan</label><textarea v-model="form.keterangan_barang" placeholder="Keterangan" class="w-full rounded-[12px] border border-[var(--color-border)] p-3 text-sm min-h-[70px]"></textarea></div>
            <div class="grid grid-cols-2 gap-3">
              <div><label class="text-[11px] font-bold">No Box</label><UiInput v-model="form.nomor_box" /></div>
              <div><label class="text-[11px] font-bold">No Laci</label><UiInput v-model="form.nomor_laci" /></div>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div><label class="text-[11px] font-bold">Stock Total</label><UiInput v-model="form.stock" type="number" /></div>
              <div><label class="text-[11px] font-bold">Batas Stock</label><UiInput v-model="form.batas_stock" type="number" /></div>
              <div><label class="text-[11px] font-bold">ID</label><input :value="editing?editing.id:'auto'" disabled class="h-10 rounded-[12px] border bg-[#FFFBF2] px-3 text-sm w-full" /></div>
            </div>

            <!-- Foto upload section -->
            <div class="border border-[var(--color-border)] rounded-[12px] p-3 bg-[#FFFBF2]/50">
              <label class="text-[11px] font-bold block mb-2">Foto Produk (JPG/PNG/WEBP max 5MB)</label>
              <div class="flex gap-3 items-start">
                <div class="w-[96px] h-[96px] rounded-[12px] border border-[#E8DDC7] bg-white overflow-hidden flex items-center justify-center">
                  <img v-if="photoPreview" :src="photoPreview" class="w-full h-full object-cover" />
                  <span v-else class="text-gray-400 text-xl">📷</span>
                </div>
                <div class="flex-1 space-y-2">
                  <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="onPhotoSelect" class="block w-full text-[11px] text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-[8px] file:border file:border-[#E8DDC7] file:bg-white file:text-[11px] file:font-bold hover:file:bg-[#F3EBD9]" />
                  <p class="text-[10px] text-gray-500">Foto akan di-upload setelah Simpan. Klik list foto untuk lihat besar.</p>
                  <div class="flex gap-2">
                    <button v-if="photoPreview" type="button" @click="clearPhoto" class="text-[10px] px-2 py-1 bg-white border border-[#E8DDC7] rounded-[6px] hover:bg-red-50 hover:text-red-600">Hapus preview</button>
                    <button v-if="editing && editing.foto" type="button" @click="deleteExistingPhoto" class="text-[10px] px-2 py-1 bg-white border border-red-200 text-red-600 rounded-[6px] hover:bg-red-50">Hapus foto tersimpan</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <UiButton variant="secondary" @click="closeModal" type="button">Batal</UiButton>
              <UiButton type="submit" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</UiButton>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- Stock adjust modal -->
    <div v-if="stockProduct" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <Card class="w-full max-w-md">
        <CardHeader><CardTitle>Adjust Stock: {{ stockProduct.nama }}</CardTitle><CardDescription>No Box {{ stockProduct.nomor_box }} No Laci {{ stockProduct.nomor_laci }} • Stock {{ stockProduct.stock }}</CardDescription></CardHeader>
        <CardContent>
          <form @submit.prevent="submitStock" class="space-y-3">
            <select v-model="stockForm.type" class="h-10 w-full rounded-[12px] border border-[var(--color-border)] px-3 text-sm">
              <option value="in">IN (+) tambah</option><option value="out">OUT (-) pakai</option><option value="adjustment">Adjustment</option>
            </select>
            <UiInput v-model="stockForm.quantity" type="number" placeholder="Qty" />
            <UiInput v-model="stockForm.reason" placeholder="Alasan" />
            <div class="flex justify-end gap-2"><UiButton variant="secondary" @click="stockProduct=null" type="button">Batal</UiButton><UiButton type="submit">Update</UiButton></div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- Photo Modal -->
    <div v-if="photoModalProduct" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-[60]" @click.self="closePhotoModal">
      <Card class="w-full max-w-3xl max-h-[90vh] overflow-auto bg-white">
        <CardHeader class="flex flex-row justify-between items-center">
          <div>
            <CardTitle class="text-[16px]">{{ photoModalProduct.nama }}</CardTitle>
            <CardDescription>ID {{ photoModalProduct.id }} • Box {{ photoModalProduct.nomor_box }} Laci {{ photoModalProduct.nomor_laci }} • {{ photoModalProduct.kategori }} • Rp{{ Number(photoModalProduct.harga).toLocaleString('id-ID') }}</CardDescription>
          </div>
          <div class="flex gap-2">
            <UiButton variant="secondary" size="sm" @click="closePhotoModal">✕ Tutup</UiButton>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="getPhotoUrl(photoModalProduct.foto)" class="bg-[#FAF6EE] flex items-center justify-center p-6 min-h-[300px]">
            <img :src="getPhotoUrl(photoModalProduct.foto)" :alt="photoModalProduct.nama" class="max-w-full max-h-[70vh] object-contain rounded-[12px] shadow-sm" />
          </div>
          <div v-else class="py-20 text-center bg-[#FFFBF2]">
            <div class="text-5xl mb-3">📷</div>
            <div class="text-sm text-gray-500">Tidak ada foto untuk barang ini</div>
            <div class="text-[11px] text-gray-400 mt-1">Gunakan tombol Edit untuk upload foto</div>
            <div class="mt-4 flex justify-center gap-2">
              <UiButton size="sm" @click="editFromPhotoModal">Upload Foto</UiButton>
              <UiButton variant="secondary" size="sm" @click="closePhotoModal">Tutup</UiButton>
            </div>
          </div>
          <div class="p-4 border-t border-[#F0E6D2] bg-white">
            <div class="text-[11px] font-bold mb-1">Keterangan</div>
            <div class="text-[12px] text-gray-700 whitespace-pre-wrap">{{ photoModalProduct.keterangan_barang || '-' }}</div>
            <div class="mt-3 grid grid-cols-3 gap-3 text-[11px]">
              <div><span class="text-gray-500">Stock:</span> <span class="font-bold">{{ photoModalProduct.stock }} / {{ photoModalProduct.batas_stock }}</span></div>
              <div><span class="text-gray-500">Lokasi:</span> <span class="font-bold">Box {{ photoModalProduct.nomor_box }} Laci {{ photoModalProduct.nomor_laci }}</span></div>
              <div><span class="text-gray-500">Total Value:</span> <span class="font-bold">Rp{{ Number(photoModalProduct.harga * photoModalProduct.stock).toLocaleString('id-ID') }}</span></div>
            </div>
            <div v-if="getPhotoUrl(photoModalProduct.foto)" class="mt-3 flex gap-2">
              <a :href="getPhotoUrl(photoModalProduct.foto)" target="_blank" class="text-[11px] px-3 py-1.5 bg-white border border-[#E8DDC7] rounded-[8px] hover:bg-[#F3EBD9] inline-flex">🔗 Buka di tab baru</a>
              <button @click="deletePhotoFromModal" class="text-[11px] px-3 py-1.5 bg-white border border-red-200 text-red-600 rounded-[8px] hover:bg-red-50">🗑️ Hapus foto</button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
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

// Photo handling
const photoFile = ref(null)
const photoPreview = ref(null)
const photoModalProduct = ref(null)

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
  } catch(e) {
    console.error('fetchProducts failed', e)
  }
}
const fetchMeta = async () => {
  try {
    const [catRes, boxRes] = await Promise.all([categoryService.getAll(), locationService.getBoxes()])
    categories.value = catRes.data.data
    boxes.value = boxRes.data.data
  } catch(e) {
    console.error('fetchMeta failed', e)
  }
}
onMounted(()=>{fetchProducts(); fetchMeta()})

const sortedProducts = computed(()=>{
  let list = [...products.value]
  const serverSortedKeys = ['id','nama','kategori','box','nomor_box','laci','nomor_laci','harga','stock','totalValue','total_value','updated','batas_stock','keterangan']
  if (serverSortedKeys.includes(sortKey.value)) {
    if (list.length > perPage.value) return list
  }
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
  } else if (sortKey.value==='keterangan' || sortKey.value==='keterangan_barang') {
    list.sort((a,b)=> sortAsc.value ? (a.keterangan_barang||'').localeCompare(b.keterangan_barang||'') : (b.keterangan_barang||'').localeCompare(a.keterangan_barang||''))
  } else if (sortKey.value==='kategori') {
    list.sort((a,b)=> sortAsc.value ? (a.kategori||'').localeCompare(b.kategori||'') : (b.kategori||'').localeCompare(a.kategori||''))
  } else if (sortKey.value==='box' || sortKey.value==='nomor_box') {
    list.sort((a,b)=> sortAsc.value ? parseInt(a.nomor_box||0)-parseInt(b.nomor_box||0) : parseInt(b.nomor_box||0)-parseInt(a.nomor_box||0))
  } else if (sortKey.value==='laci' || sortKey.value==='nomor_laci') {
    list.sort((a,b)=> sortAsc.value ? parseInt(a.nomor_laci||0)-parseInt(b.nomor_laci||0) : parseInt(b.nomor_laci||0)-parseInt(a.nomor_laci||0))
  } else if (sortKey.value==='batas_stock') {
    list.sort((a,b)=> sortAsc.value ? a.batas_stock-b.batas_stock : b.batas_stock-a.batas_stock)
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
  showCreate.value=false
  editing.value=null
  Object.assign(form,{nama:'',kategori:'',keterangan_barang:'',nomor_box:'',nomor_laci:'',harga:0,stock:0,batas_stock:10})
  clearPhoto()
}

const saveProduct = async () => {
  if (saving.value) return
  saving.value=true
  try{
    let savedId = editing.value?.id
    let savedProduct
    if(editing.value) {
      await productService.update(editing.value.id,form)
      savedId = editing.value.id
      savedProduct = { ...editing.value, ...form }
    } else {
      const res = await productService.create(form)
      savedId = res.data.data.id
      savedProduct = res.data.data
    }
    if (photoFile.value && savedId) {
      try {
        const upRes = await productService.uploadPhoto(savedId, photoFile.value)
        savedProduct = upRes.data.data
      } catch(e) {
        alert('Barang tersimpan tapi foto gagal upload: ' + (e.response?.data?.error||e.message))
      }
    }
    closeModal()
    fetchProducts()
    fetchMeta()
  } catch(e){ alert(e.response?.data?.error||e.message) }
  finally { saving.value=false }
}

const editProduct = (p)=>{
  editing.value=p
  Object.assign(form,{nama:p.nama,kategori:p.kategori,keterangan_barang:p.keterangan_barang,nomor_box:p.nomor_box,nomor_laci:p.nomor_laci,harga:p.harga,stock:p.stock,batas_stock:p.batas_stock})
  photoFile.value=null
  photoPreview.value = p.foto ? getPhotoUrl(p.foto) : null
}

const deleteProduct = async (id)=>{
  if(!confirm('Hapus barang ini?')) return
  try { await productService.delete(id); fetchProducts() } catch(e){ alert(e.response?.data?.error||e.message) }
}

const deleteExistingPhoto = async () => {
  if (!editing.value?.id) return
  if (!confirm('Hapus foto tersimpan? File akan dihapus permanen.')) return
  try {
    await productService.deletePhoto(editing.value.id)
    alert('Foto dihapus')
    editing.value.foto = null
    photoPreview.value = null
    fetchProducts()
  } catch(e){ alert(e.response?.data?.error||e.message) }
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

// Photo modal logic
const openPhotoModal = (p) => { photoModalProduct.value = p }
const closePhotoModal = () => { photoModalProduct.value = null }
const editFromPhotoModal = () => {
  const p = photoModalProduct.value
  closePhotoModal()
  if (p) editProduct(p)
}
const deletePhotoFromModal = async () => {
  if (!photoModalProduct.value) return
  if (!confirm('Hapus foto ini?')) return
  try {
    await productService.deletePhoto(photoModalProduct.value.id)
    alert('Foto dihapus')
    photoModalProduct.value.foto = null
    fetchProducts()
    closePhotoModal()
  } catch(e){ alert(e.response?.data?.error||e.message) }
}
const onImgError = (e) => { e.target.style.display='none' }
</script>
