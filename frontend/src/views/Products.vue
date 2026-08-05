<template>
  <div>
    <!-- Header like MONITORING OVERVIEW -->
    <div class="mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">INVENTORY OVERVIEW</div>
      <div class="flex justify-between items-end flex-wrap gap-3">
        <div>
          <h1 class="text-[32px] font-extrabold tracking-tight leading-none mt-1">Data Barang</h1>
          <p class="text-[12px] text-gray-500 mt-1">Manajemen {{ meta.total }} jenis komponen • Box-Laci tracking Yogyakarta</p>
        </div>
        <div class="flex gap-2">
          <UiButton variant="secondary" @click="exportCsv">📥 Export CSV</UiButton>
          <label class="cursor-pointer inline-flex items-center justify-center h-10 px-4 rounded-[12px] bg-[#0F1E35] text-white text-sm font-medium">
            📤 Import CSV
            <input type="file" accept=".csv" class="hidden" @change="onImportFile" />
          </label>
          <UiButton @click="showCreate = true">+ Tambah</UiButton>
        </div>
      </div>
    </div>

    <!-- Filters card -->
    <Card class="mb-5">
      <CardContent class="flex flex-wrap gap-3 items-center">
        <div class="flex-1 min-w-[220px]">
          <UiInput v-model="search" @input="debouncedFetch" placeholder="Cari nama / kategori / box..." />
        </div>
        <select v-model="kategoriFilter" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option value="">Semua Kategori</option>
          <option v-for="c in categories" :key="c.kategori" :value="c.kategori">{{ c.kategori }} ({{ c.product_count }})</option>
        </select>
        <select v-model="boxFilter" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option value="">Semua Box</option>
          <option v-for="b in boxes" :key="b.nomor_box" :value="b.nomor_box">Box {{ b.nomor_box }}</option>
        </select>
        <label class="flex items-center gap-1.5 text-[12px]"><input type="checkbox" v-model="lowStockOnly" @change="resetAndFetch" /> Low stock</label>
        <select v-model="perPage" @change="resetAndFetch" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option :value="10">10 / hal</option>
          <option :value="20">20 / hal</option>
          <option :value="50">50 / hal</option>
          <option :value="100">100 / hal</option>
        </select>
      </CardContent>
    </Card>

    <!-- Table card -->
    <Card>
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[12px]">
          <thead class="border-b border-[var(--color-border)] bg-[#FFFBF2]">
            <tr class="text-[10px] tracking-[0.14em] text-gray-500 font-semibold text-left">
              <th class="py-3 px-4">ID</th>
              <th>Nama</th>
              <th>Kategori</th>
              <th>Ket</th>
              <th>Box</th>
              <th>Laci</th>
              <th>Harga</th>
              <th>Stock</th>
              <th>Batas</th>
              <th>Updated</th>
              <th class="pr-4">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in products" :key="p.id" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2] transition whitespace-nowrap" :class="{'bg-red-50/60': p.stock <= p.batas_stock}">
              <td class="py-3 px-4 font-mono text-[11px]">{{ p.id }}</td>
              <td class="font-semibold max-w-[220px] truncate" :title="p.nama">{{ p.nama }}</td>
              <td><Badge variant="blue">{{ p.kategori }}</Badge></td>
              <td class="max-w-[160px] truncate text-gray-500">{{ p.keterangan_barang }}</td>
              <td><Badge variant="secondary">Box {{ p.nomor_box }}</Badge></td>
              <td class="text-gray-600">Laci {{ p.nomor_laci }}</td>
              <td class="font-mono">Rp{{ Number(p.harga).toLocaleString('id-ID') }}</td>
              <td><span :class="p.stock <= p.batas_stock ? 'text-[#DC2626] font-bold' : 'font-semibold'">{{ p.stock }}</span></td>
              <td class="text-gray-500">{{ p.batas_stock }}</td>
              <td class="text-[11px] text-gray-400">{{ new Date(p.updated).toLocaleDateString('id-ID') }}</td>
              <td class="pr-4 flex gap-2">
                <button @click="openStockModal(p)" class="text-[#0F1E35] hover:underline text-[11px] font-semibold">Stock</button>
                <button @click="editProduct(p)" class="text-[#0F1E35]/60 hover:text-[#0F1E35] text-[11px]">Edit</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="products.length===0" class="py-12 text-center text-sm text-gray-500">Tidak ada data</div>
      </CardContent>
    </Card>

    <!-- Pagination footer -->
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

    <!-- Modals -->
    <div v-if="showCreate || editing" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <Card class="w-full max-w-lg max-h-[90vh] overflow-auto">
        <CardHeader>
          <CardTitle>{{ editing ? 'Edit Barang' : 'Tambah Barang' }}</CardTitle>
          <CardDescription>Inventory_pcbexpressjogja • data_barang</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="saveProduct" class="space-y-3">
            <UiInput v-model="form.nama" placeholder="Nama Barang *" />
            <div class="grid grid-cols-2 gap-3">
              <input v-model="form.kategori" list="kat-list" placeholder="Kategori" class="h-10 rounded-[12px] border border-[var(--color-border)] px-3 text-sm" />
              <UiInput v-model="form.harga" type="number" placeholder="Harga Rp" />
            </div>
            <datalist id="kat-list"><option v-for="c in categories" :key="c.kategori" :value="c.kategori" /></datalist>
            <textarea v-model="form.keterangan_barang" placeholder="Keterangan" class="w-full rounded-[12px] border border-[var(--color-border)] p-3 text-sm min-h-[70px]"></textarea>
            <div class="grid grid-cols-2 gap-3">
              <UiInput v-model="form.nomor_box" placeholder="Box" />
              <UiInput v-model="form.nomor_laci" placeholder="Laci" />
            </div>
            <div class="grid grid-cols-3 gap-3">
              <UiInput v-model="form.stock" type="number" placeholder="Stock" />
              <UiInput v-model="form.batas_stock" type="number" placeholder="Batas" />
              <input :value="editing?editing.id:'auto'" disabled class="h-10 rounded-[12px] border bg-[#FFFBF2] px-3 text-sm" />
            </div>
            <div class="flex justify-end gap-2 pt-2">
              <UiButton variant="secondary" @click="closeModal">Batal</UiButton>
              <UiButton type="submit">Simpan</UiButton>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <div v-if="stockProduct" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <Card class="w-full max-w-md">
        <CardHeader>
          <CardTitle>Adjust Stock</CardTitle>
          <CardDescription>Box {{ stockProduct.nomor_box }} Laci {{ stockProduct.nomor_laci }} • {{ stockProduct.stock }} pcs</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submitStock" class="space-y-3">
            <select v-model="stockForm.type" class="h-10 w-full rounded-[12px] border border-[var(--color-border)] px-3 text-sm">
              <option value="in">IN (+) tambah</option>
              <option value="out">OUT (-) pakai</option>
              <option value="adjustment">Adjustment set absolute</option>
            </select>
            <UiInput v-model="stockForm.quantity" type="number" placeholder="Qty" />
            <UiInput v-model="stockForm.reason" placeholder="Alasan" />
            <div class="flex justify-end gap-2">
              <UiButton variant="secondary" @click="stockProduct=null">Batal</UiButton>
              <UiButton type="submit">Update</UiButton>
            </div>
          </form>
          <div v-if="stockProduct.history" class="mt-4 border-t pt-3">
            <div class="text-[11px] font-bold mb-2">Riwayat log_stock</div>
            <ul class="text-[11px] max-h-32 overflow-auto space-y-1">
              <li v-for="h in stockProduct.history.slice(0,10)" :key="h.no" class="flex justify-between border-b border-[#F5EFE4] py-1">
                <span :class="h.stock>0?'text-green-600':'text-red-600'">{{ h.stock>0?'+':'' }}{{ h.stock }}</span>
                <span class="text-gray-400">{{ new Date(h.waktu).toLocaleDateString() }}</span>
              </li>
            </ul>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { productService, categoryService, locationService } from '../services/api'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardDescription from '../components/ui/CardDescription.vue'
import CardContent from '../components/ui/CardContent.vue'
import Badge from '../components/ui/Badge.vue'
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

const form = reactive({ nama:'', kategori:'', keterangan_barang:'', nomor_box:'', nomor_laci:'', harga:0, stock:0, batas_stock:10 })
const stockForm = reactive({ type:'in', quantity:1, reason:'' })

let timer=null
const debouncedFetch = ()=>{ clearTimeout(timer); timer=setTimeout(()=>{meta.value.page=1; fetchProducts()},350) }
const resetAndFetch = ()=>{ meta.value.page=1; fetchProducts() }

const fetchProducts = async () => {
  const params = { page: meta.value.page, per_page: perPage.value, search: search.value||undefined, kategori: kategoriFilter.value||undefined, nomor_box: boxFilter.value||undefined, low_stock: lowStockOnly.value?1:undefined }
  const res = await productService.getAll(params)
  products.value = res.data.data
  if(res.data.meta) meta.value = res.data.meta
}
const fetchMeta = async () => {
  const [catRes, boxRes] = await Promise.all([categoryService.getAll(), locationService.getBoxes()])
  categories.value = catRes.data.data
  boxes.value = boxRes.data.data
}
onMounted(()=>{fetchProducts(); fetchMeta()})

const visiblePages = computed(()=>{
  const total=meta.value.total_pages, cur=meta.value.page
  if(total<=7) return Array.from({length:total},(_,i)=>i+1)
  const pages=[1]; if(cur>3) pages.push('...'); for(let i=Math.max(2,cur-1); i<=Math.min(total-1,cur+1); i++) pages.push(i); if(cur<total-2) pages.push('...'); pages.push(total); return pages
})
const goPage = (p)=>{ if(p<1||p>meta.value.total_pages) return; meta.value.page=p; fetchProducts(); window.scrollTo({top:0,behavior:'smooth'}) }
const closeModal = ()=>{ showCreate.value=false; editing.value=null; Object.assign(form,{nama:'',kategori:'',keterangan_barang:'',nomor_box:'',nomor_laci:'',harga:0,stock:0,batas_stock:10}) }
const saveProduct = async () => { try{ if(editing.value) await productService.update(editing.value.id,form); else await productService.create(form); closeModal(); fetchProducts(); fetchMeta() } catch(e){ alert(e.response?.data?.error||e.message) } }
const editProduct = (p)=>{ editing.value=p; Object.assign(form,{nama:p.nama,kategori:p.kategori,keterangan_barang:p.keterangan_barang,nomor_box:p.nomor_box,nomor_laci:p.nomor_laci,harga:p.harga,stock:p.stock,batas_stock:p.batas_stock}) }
const openStockModal = async (p)=>{ const res=await productService.getOne(p.id); stockProduct.value=res.data.data; stockForm.quantity=1; stockForm.type='in'; stockForm.reason='' }
const submitStock = async ()=>{ try{ await productService.adjustStock(stockProduct.value.id,stockForm); stockProduct.value=null; fetchProducts() } catch(e){ alert(e.response?.data?.error||e.message) } }

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
</script>
