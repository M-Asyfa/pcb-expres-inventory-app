<template>
  <div>
    <div class="mb-4 lg:mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">LOCATION OVERVIEW</div>
      <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-3">
        <div>
          <h1 class="text-[24px] lg:text-[32px] font-extrabold tracking-tight leading-none mt-1">Box / Laci</h1>
          <p class="text-[11px] lg:text-[12px] text-gray-500 mt-1">{{ filteredLocations.length }} dari {{ locations.length }} lokasi • {{ boxes.length }} box • {{ totalJenis }} jenis</p>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
          <UiButton variant="secondary" @click="exportLocationsCsv" class="flex-1 lg:flex-none h-11 lg:h-10 text-[12px]">📥 Export CSV</UiButton>
        </div>
      </div>
    </div>

    <Card class="mb-4 lg:mb-5">
      <CardContent class="p-3 lg:p-4 space-y-3">
        <div class="flex flex-col lg:flex-row gap-2 lg:gap-3 lg:items-center justify-between">
          <div class="flex flex-col lg:flex-row gap-2 lg:gap-3 lg:items-center flex-1">
            <UiInput v-model="search" placeholder="Cari Box / Laci / Nama..." class="w-full lg:w-[260px] h-11 lg:h-10 text-[14px]" />
            <select v-model="boxFilter" class="h-11 lg:h-10 rounded-[12px] border border-[#E8DDC7] bg-white px-3 text-[12px] lg:text-sm">
              <option value="">Semua Box</option>
              <option v-for="b in boxes" :key="b.nomor_box" :value="b.nomor_box">Box {{ b.nomor_box }} ({{ b.product_count }} jenis)</option>
            </select>
          </div>
          <div class="flex gap-2 items-center justify-between lg:justify-end">
            <span class="text-[11px] text-gray-500">Tampilkan</span>
            <select v-model="perPage" class="h-9 rounded-[10px] border border-[#E8DDC7] bg-white px-2 text-[11px] lg:text-[12px]">
              <option :value="20">20</option><option :value="50">50</option><option :value="100">100</option>
            </select>
            <span class="text-[11px] text-gray-500">/ {{ totalPages }} hal</span>
          </div>
        </div>
      </CardContent>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">
      <Card class="lg:col-span-2">
        <CardHeader class="flex flex-row justify-between items-center py-3 px-4">
          <div class="min-w-0">
            <CardTitle class="text-[13px]">Daftar Lokasi</CardTitle>
            <CardDescription class="text-[11px] truncate">Kombinasi unik Box-Laci terpakai di gudang – tap View untuk modal</CardDescription>
          </div>
          <Badge variant="secondary" class="hidden lg:inline-flex">{{ filteredLocations.length }} lokasi</Badge>
        </CardHeader>
        <CardContent class="p-0 overflow-x-auto hidden lg:block">
          <table class="w-full text-[11px]">
            <thead class="border-b border-[#E8DDC7] bg-[#FFFBF2]">
              <tr class="text-[11px] font-bold text-[#0F172A] text-left tracking-wide">
                <th class="py-3 px-4 cursor-pointer whitespace-nowrap" @click="sortBy('box')">No Box ↕</th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('laci')">No Laci ↕</th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('name')">Nama Lokasi ↕</th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('count')">Jumlah Jenis ↕</th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('stock')">Total Stock ↕</th>
                <th class="py-3 pr-4 whitespace-nowrap">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in pagedLocations" :key="l.id" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2] transition">
                <td class="py-3 px-4 font-mono font-bold">Box {{ l.nomor_box }}</td>
                <td>Laci {{ l.nomor_laci }}</td>
                <td class="font-semibold">{{ l.name }}</td>
                <td><Badge variant="secondary">{{ l.product_count }} jenis</Badge></td>
                <td class="font-bold">{{ l.total_stock }}</td>
                <td class="pr-4"><UiButton @click="viewProducts(l)" variant="secondary" size="sm" class="h-[28px] rounded-[8px] text-[11px]">View</UiButton></td>
              </tr>
              <tr v-if="filteredLocations.length===0"><td colspan="6" class="py-12 text-center text-gray-400 text-[12px]">Tidak ada lokasi</td></tr>
            </tbody>
          </table>
        </CardContent>
        <CardContent class="p-3 lg:hidden space-y-2">
          <div v-for="l in pagedLocations" :key="l.id" class="bg-[#FFFBF2] border border-[#F0E6D2] rounded-[12px] p-3 flex justify-between items-center">
            <div class="min-w-0 flex-1">
              <div class="flex gap-2 items-center">
                <span class="px-2 py-1 bg-white border border-[#E8DDC7] rounded-[8px] text-[11px] font-mono font-bold">Box {{ l.nomor_box }}</span>
                <span class="px-2 py-1 bg-white border border-[#E8DDC7] rounded-[8px] text-[11px] font-mono">Laci {{ l.nomor_laci }}</span>
                <Badge variant="secondary" class="text-[10px]">{{ l.product_count }} jenis</Badge>
              </div>
              <div class="text-[11px] font-semibold mt-1.5 truncate">{{ l.name }}</div>
              <div class="text-[10px] text-gray-500 mt-0.5">Total stock: <span class="font-bold text-[#0F1E35]">{{ l.total_stock }}</span></div>
            </div>
            <UiButton @click="viewProducts(l)" variant="secondary" size="sm" class="h-9 rounded-[10px] ml-2">View</UiButton>
          </div>
          <div v-if="filteredLocations.length===0" class="py-12 text-center text-gray-400 text-[12px]">Tidak ada lokasi</div>
        </CardContent>
        <div class="flex justify-between items-center p-3 border-t">
          <span class="text-[11px] text-gray-500">Hal {{ currentPage }} / {{ totalPages }} • {{ filteredLocations.length }}</span>
          <div class="flex gap-1">
            <UiButton variant="secondary" size="sm" @click="currentPage=Math.max(1,currentPage-1)" :disabled="currentPage<=1" class="h-8">‹</UiButton>
            <span class="px-3 py-1 bg-[#0F1E35] text-white rounded-[8px] text-[11px] flex items-center">{{ currentPage }}</span>
            <UiButton variant="secondary" size="sm" @click="currentPage=Math.min(totalPages,currentPage+1)" :disabled="currentPage>=totalPages" class="h-8">›</UiButton>
          </div>
        </div>
      </Card>

      <Card>
        <CardHeader class="px-4 py-3">
          <CardTitle class="text-[13px]">Box Summary</CardTitle>
          <CardDescription class="text-[11px]">{{ boxes.length }} box fisik • Tap Filter</CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <div class="hidden lg:block overflow-x-auto">
            <table class="w-full text-[11px]">
              <thead class="border-b bg-[#FFFBF2] text-[10px] tracking-wide text-gray-500 font-bold">
                <tr><th class="py-2.5 px-4 text-left">No Box</th><th class="text-left">Laci</th><th class="text-left">Jenis</th><th class="pr-4"></th></tr>
              </thead>
              <tbody>
                <tr v-for="b in boxes" :key="b.nomor_box" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2] transition">
                  <td class="py-2.5 px-4 font-bold">Box {{ b.nomor_box }}</td>
                  <td>{{ getLaciCount(b.nomor_box) }}</td>
                  <td><Badge variant="secondary">{{ b.product_count }}</Badge></td>
                  <td class="pr-4"><button @click="filterByBox(b.nomor_box)" class="text-[11px] font-bold text-[#0F1E35] hover:underline">Filter</button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="lg:hidden grid grid-cols-2 gap-2 p-3">
            <div v-for="b in boxes" :key="b.nomor_box" class="bg-white border border-[#F0E6D2] rounded-[12px] p-3">
              <div class="font-bold text-[12px]">Box {{ b.nomor_box }}</div>
              <div class="text-[10px] text-gray-500 mt-1">{{ getLaciCount(b.nomor_box) }} laci • {{ b.product_count }} jenis</div>
              <button @click="filterByBox(b.nomor_box)" class="mt-2 w-full h-8 bg-[#FFFBF2] border border-[#E8DDC7] rounded-[8px] text-[11px] font-bold">Filter</button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Modal for location products -->
    <div v-if="showModal && selectedLocation" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-end lg:items-center justify-center p-0 lg:p-4 z-50" @click.self="closeModal">
      <Card class="w-full max-w-4xl max-h-[92vh] lg:max-h-[90vh] overflow-auto rounded-t-[20px] lg:rounded-[20px] flex flex-col">
        <CardHeader class="flex flex-row justify-between items-center px-4 py-3 sticky top-0 bg-white z-10 border-b border-[#F0E6D2]">
          <div class="min-w-0">
            <CardTitle class="text-[14px] truncate">Barang di {{ selectedLocation.name }}</CardTitle>
            <CardDescription class="text-[11px]">{{ loadingDetail ? 'Memuat...' : (selectedProducts.length + ' jenis • Box ' + selectedLocation.nomor_box + ' Laci ' + selectedLocation.nomor_laci) }}</CardDescription>
          </div>
          <div class="flex gap-2 flex-shrink-0">
            <UiButton variant="secondary" size="sm" @click="closeModal" class="rounded-[8px] h-9">✕ Tutup</UiButton>
          </div>
        </CardHeader>
        <CardContent class="p-0 overflow-auto flex-1">
          <div v-if="loadingDetail" class="py-16 text-center text-[13px] text-gray-500">Memuat barang di {{ selectedLocation.name }}...</div>
          <template v-else>
            <div class="hidden lg:block overflow-x-auto">
              <table class="w-full text-[11px]">
                <thead class="border-b bg-[#FFFBF2] text-[10px] tracking-wide text-gray-500 font-bold sticky top-0">
                  <tr><th class="py-2.5 px-4 text-left">ID</th><th class="text-left">Name / Nama</th><th class="text-left">Kategori</th><th class="text-left">Stock</th><th class="text-left">Harga (Rp)</th><th class="text-left">Total Value (Rp)</th></tr>
                </thead>
                <tbody>
                  <tr v-for="p in selectedProducts" :key="p.id" class="border-b hover:bg-[#FFFBF2]">
                    <td class="py-2.5 px-4 font-mono">{{ p.id }}</td>
                    <td class="font-semibold max-w-[260px] truncate" :title="p.nama">{{ p.nama }}</td>
                    <td><Badge variant="blue">{{ p.kategori }}</Badge></td>
                    <td :class="p.stock<=p.batas_stock?'text-red-600 font-bold':''">{{ p.stock }}</td>
                    <td class="font-mono">Rp{{ Number(p.harga).toLocaleString('id-ID') }}</td>
                    <td class="font-mono font-bold">Rp{{ Number(p.harga * p.stock).toLocaleString('id-ID') }}</td>
                  </tr>
                  <tr v-if="selectedProducts.length===0"><td colspan="6" class="py-12 text-center text-gray-400">Tidak ada barang di lokasi ini</td></tr>
                </tbody>
              </table>
            </div>
            <div class="lg:hidden p-3 space-y-2">
              <div v-for="p in selectedProducts" :key="p.id" class="border border-[#F0E6D2] rounded-[12px] p-3 bg-[#FFFBF2]/50">
                <div class="flex justify-between gap-2">
                  <div class="font-bold text-[13px] truncate flex-1">{{ p.nama }}</div>
                  <div class="font-mono text-[10px] text-gray-500">#{{ p.id }}</div>
                </div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                  <Badge variant="blue" class="text-[10px]">{{ p.kategori }}</Badge>
                  <span class="text-[11px] px-2 py-1 bg-white border border-[#E8DDC7] rounded-full">Stok {{ p.stock }}</span>
                  <span class="text-[11px] px-2 py-1 bg-white border border-[#E8DDC7] rounded-full font-mono">Rp{{ Number(p.harga).toLocaleString('id-ID') }}</span>
                </div>
                <div class="text-[10px] text-gray-500 mt-1.5">Total: <span class="font-bold text-[#0F1E35]">Rp{{ Number(p.harga * p.stock).toLocaleString('id-ID') }}</span></div>
              </div>
              <div v-if="selectedProducts.length===0" class="py-10 text-center text-gray-400 text-[12px]">Tidak ada barang di lokasi ini</div>
            </div>
          </template>
        </CardContent>
        <div class="p-3 border-t border-[#F0E6D2] flex justify-end gap-2 bg-[#FFFBF2]/50">
          <UiButton variant="secondary" @click="closeModal" class="h-10">Tutup</UiButton>
          <UiButton @click="exportDetailCsv" class="h-10 text-[12px]">📥 Export CSV lokasi ini</UiButton>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { locationService } from '../services/api'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardDescription from '../components/ui/CardDescription.vue'
import CardContent from '../components/ui/CardContent.vue'
import Badge from '../components/ui/Badge.vue'
import UiButton from '../components/ui/Button.vue'
import UiInput from '../components/ui/Input.vue'

const locations = ref([])
const boxes = ref([])
const selectedLocation = ref(null)
const selectedProducts = ref([])
const search = ref('')
const boxFilter = ref('')
const perPage = ref(50)
const currentPage = ref(1)
const sortKey = ref('box')
const sortAsc = ref(true)
const showModal = ref(false)
const loadingDetail = ref(false)

const fetch = async () => {
  try {
    const [locRes, boxRes] = await Promise.all([locationService.getAll('full'), locationService.getBoxes()])
    locations.value = locRes.data.data
    boxes.value = boxRes.data.data
  } catch(e) {
    console.error('fetch locations failed', e)
    const msg = e.response?.data?.error || e.message
    alert('Gagal load lokasi: ' + msg)
  }
}
onMounted(fetch)
watch([search, boxFilter], ()=>{ currentPage.value=1 })

const filteredLocations = computed(()=>{
  let list = locations.value
  if (search.value) { const s=search.value.toLowerCase(); list=list.filter(l=> String(l.nomor_box).includes(s) || String(l.nomor_laci).includes(s) || String(l.name).toLowerCase().includes(s)) }
  if (boxFilter.value) list=list.filter(l=> String(l.nomor_box)===String(boxFilter.value))
  list = [...list].sort((a,b)=>{
    const k = sortKey.value
    if (k==='box') return sortAsc.value ? parseInt(a.nomor_box)-parseInt(b.nomor_box) : parseInt(b.nomor_box)-parseInt(a.nomor_box)
    if (k==='laci') return sortAsc.value ? parseInt(a.nomor_laci)-parseInt(b.nomor_laci) : parseInt(b.nomor_laci)-parseInt(a.nomor_laci)
    if (k==='count') return sortAsc.value ? a.product_count-b.product_count : b.product_count-a.product_count
    if (k==='stock') return sortAsc.value ? (a.total_stock||0)-(b.total_stock||0) : (b.total_stock||0)-(a.total_stock||0)
    if (k==='name') return sortAsc.value ? a.name.localeCompare(b.name) : b.name.localeCompare(a.name)
    return 0
  })
  return list
})

const totalPages = computed(()=> Math.max(1, Math.ceil(filteredLocations.value.length / perPage.value)))
const pagedLocations = computed(()=> { const start=(currentPage.value-1)*perPage.value; return filteredLocations.value.slice(start,start+perPage.value) })
const totalJenis = computed(()=> filteredLocations.value.reduce((s,l)=> s + (parseInt(l.product_count)||0), 0))
const getLaciCount = (box) => locations.value.filter(l=> String(l.nomor_box)===String(box)).length

const viewProducts = async (loc) => {
  selectedLocation.value = loc
  selectedProducts.value = []
  showModal.value = true
  loadingDetail.value = true
  try {
    const res = await locationService.getProducts(loc.nomor_box, loc.nomor_laci)
    selectedProducts.value = res.data.data || []
  } catch(e) {
    console.error('viewProducts failed', e)
    const msg = e.response?.data?.error || e.message || 'Gagal load barang di lokasi ini'
    alert(`Gagal buka Box ${loc.nomor_box} Laci ${loc.nomor_laci}: ${msg}`)
  } finally {
    loadingDetail.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  // keep selectedLocation for a moment for animation, then clear? we clear after close
  setTimeout(()=>{
    if (!showModal.value) { selectedLocation.value = null; selectedProducts.value = [] }
  }, 200)
}

const filterByBox = (box)=>{ boxFilter.value=String(box); currentPage.value=1 }
const sortBy = (k)=>{ if(sortKey.value===k) sortAsc.value=!sortAsc.value; else { sortKey.value=k; sortAsc.value=true } }

const exportLocationsCsv = () => {
  const csv = ['No Box,No Laci,Nama Lokasi,Jumlah Jenis,Total Stock']
  filteredLocations.value.forEach(l=>{ csv.push(`${l.nomor_box},${l.nomor_laci},"${l.name}",${l.product_count},${l.total_stock||0}`) })
  const blob = new Blob([csv.join('\n')], {type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='box-laci.csv'; a.click(); URL.revokeObjectURL(url)
}

const exportDetailCsv = () => {
  if (!selectedLocation.value) return
  const loc = selectedLocation.value
  const header = ['id','nama','kategori','nomor_box','nomor_laci','harga','stock','total_value']
  const rows = [header.join(',')]
  selectedProducts.value.forEach(p=>{
    const total = p.harga * p.stock
    rows.push([p.id, `"${(p.nama||'').replace(/"/g,'""')}"`, `"${(p.kategori||'').replace(/"/g,'""')}"`, p.nomor_box, p.nomor_laci, p.harga, p.stock, total].join(','))
  })
  const blob = new Blob([rows.join('\n')], {type:'text/csv'})
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url; a.download = `box-${loc.nomor_box}-laci-${loc.nomor_laci}.csv`; a.click()
  URL.revokeObjectURL(url)
}
</script>
