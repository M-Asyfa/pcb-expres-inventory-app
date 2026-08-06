<template>
  <div>
    <div class="mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">LOCATION OVERVIEW</div>
      <div class="flex justify-between items-end flex-wrap gap-3">
        <div>
          <h1 class="text-[32px] font-extrabold tracking-tight leading-none mt-1">Box / Laci</h1>
          <p class="text-[12px] text-gray-500 mt-1">{{ filteredLocations.length }} dari {{ locations.length }} lokasi • {{ boxes.length }} box fisik • {{ totalJenis }} jenis total</p>
        </div>
        <div class="flex gap-2">
          <UiButton variant="secondary" @click="exportLocationsCsv">📥 Export CSV</UiButton>
        </div>
      </div>
    </div>

    <Card class="mb-5">
      <CardContent class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex gap-3 items-center flex-wrap">
          <UiInput v-model="search" placeholder="Cari Box / Laci / Nama..." class="w-[260px]" />
          <select v-model="boxFilter" class="h-10 rounded-[12px] border border-[#E8DDC7] bg-white px-3 text-sm">
            <option value="">Semua Box</option>
            <option v-for="b in boxes" :key="b.nomor_box" :value="b.nomor_box">Box {{ b.nomor_box }} ({{ b.product_count }} jenis)</option>
          </select>
        </div>
        <div class="flex gap-2 items-center">
          <span class="text-[11px] text-gray-500">Tampilkan</span>
          <select v-model="perPage" class="h-9 rounded-[10px] border border-[#E8DDC7] bg-white px-2 text-[11px]">
            <option :value="20">20</option><option :value="50">50</option><option :value="100">100</option>
          </select>
          <span class="text-[11px] text-gray-500">/ {{ totalPages }} hal</span>
        </div>
      </CardContent>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <Card class="lg:col-span-2">
        <CardHeader class="flex flex-row justify-between items-center py-3">
          <div>
            <CardTitle class="text-[13px]">Daftar Lokasi</CardTitle>
            <CardDescription class="text-[11px]">Kombinasi unik Box-Laci yang terpakai di gudang</CardDescription>
          </div>
          <Badge variant="secondary">{{ filteredLocations.length }} lokasi</Badge>
        </CardHeader>
        <CardContent class="p-0 overflow-x-auto">
          <table class="w-full text-[11px]">
            <thead class="border-b border-[#E8DDC7] bg-[#FFFBF2]">
              <tr class="text-[11px] font-bold text-[#0F172A] text-left tracking-wide">
                <th class="py-3 px-4 cursor-pointer whitespace-nowrap" @click="sortBy('box')">No Box <span class="text-[#E8DDC7]">↕</span></th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('laci')">No Laci <span class="text-[#E8DDC7]">↕</span></th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('name')">Nama Lokasi <span class="text-[#E8DDC7]">↕</span></th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('count')">Jumlah Jenis <span class="text-[#E8DDC7]">↕</span></th>
                <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('stock')">Total Stock <span class="text-[#E8DDC7]">↕</span></th>
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
          <div class="flex justify-between items-center p-3 border-t">
            <span class="text-[11px] text-gray-500">Hal {{ currentPage }} dari {{ totalPages }} • {{ filteredLocations.length }} lokasi</span>
            <div class="flex gap-1">
              <UiButton variant="secondary" size="sm" @click="currentPage=Math.max(1,currentPage-1)" :disabled="currentPage<=1">‹</UiButton>
              <span class="px-3 py-1 bg-[#0F1E35] text-white rounded-[8px] text-[11px]">{{ currentPage }}</span>
              <UiButton variant="secondary" size="sm" @click="currentPage=Math.min(totalPages,currentPage+1)" :disabled="currentPage>=totalPages">›</UiButton>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="text-[13px]">Box Summary</CardTitle>
          <CardDescription class="text-[11px]">{{ boxes.length }} box fisik • Klik filter</CardDescription>
        </CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-[11px]">
            <thead class="border-b bg-[#FFFBF2] text-[10px] tracking-wide text-gray-500 font-bold">
              <tr><th class="py-2.5 px-4 text-left">No Box</th><th class="text-left">Laci Count</th><th class="text-left">Jumlah Jenis</th><th class="pr-4"></th></tr>
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
        </CardContent>
      </Card>
    </div>

    <Card v-if="selectedLocation" class="mt-5">
      <CardHeader class="flex flex-row justify-between items-center">
        <div>
          <CardTitle class="text-[13px]">Barang di {{ selectedLocation.name }}</CardTitle>
          <CardDescription class="text-[11px]">{{ selectedProducts.length }} jenis • Box {{ selectedLocation.nomor_box }} Laci {{ selectedLocation.nomor_laci }}</CardDescription>
        </div>
        <UiButton variant="secondary" size="sm" @click="selectedLocation=null" class="rounded-[8px]">Tutup</UiButton>
      </CardHeader>
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[11px]">
          <thead class="border-b bg-[#FFFBF2] text-[10px] tracking-wide text-gray-500 font-bold">
            <tr><th class="py-2.5 px-4 text-left">ID</th><th class="text-left">Name / Nama</th><th class="text-left">Kategori</th><th class="text-left">Stock Total</th><th class="text-left">Harga (Rp)</th><th class="text-left">Total Value (Rp)</th></tr>
          </thead>
          <tbody>
            <tr v-for="p in selectedProducts" :key="p.id" class="border-b hover:bg-[#FFFBF2]">
              <td class="py-2.5 px-4 font-mono">{{ p.id }}</td>
              <td class="font-semibold max-w-[200px] truncate" :title="p.nama">{{ p.nama }}</td>
              <td><Badge variant="blue">{{ p.kategori }}</Badge></td>
              <td :class="p.stock<=p.batas_stock?'text-red-600 font-bold':''">{{ p.stock }}</td>
              <td class="font-mono">Rp{{ Number(p.harga).toLocaleString('id-ID') }}</td>
              <td class="font-mono font-bold">Rp{{ Number(p.harga * p.stock).toLocaleString('id-ID') }}</td>
            </tr>
          </tbody>
        </table>
      </CardContent>
    </Card>
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

const fetch = async () => {
  const [locRes, boxRes] = await Promise.all([locationService.getAll('full'), locationService.getBoxes()])
  locations.value = locRes.data.data
  boxes.value = boxRes.data.data
}
onMounted(fetch)
watch([search, boxFilter], ()=>{ currentPage.value=1 })

const filteredLocations = computed(()=>{
  let list = locations.value
  if (search.value) { const s=search.value.toLowerCase(); list=list.filter(l=> String(l.nomor_box).includes(s) || String(l.nomor_laci).includes(s) || String(l.name).toLowerCase().includes(s)) }
  if (boxFilter.value) list=list.filter(l=> String(l.nomor_box)===String(boxFilter.value))
  // sort
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
  const res = await locationService.getProducts(loc.nomor_box, loc.nomor_laci)
  selectedProducts.value = res.data.data
}
const filterByBox = (box)=>{ boxFilter.value=String(box); currentPage.value=1 }
const sortBy = (k)=>{ if(sortKey.value===k) sortAsc.value=!sortAsc.value; else { sortKey.value=k; sortAsc.value=true } }
const exportLocationsCsv = () => {
  const csv = ['No Box,No Laci,Nama Lokasi,Jumlah Jenis,Total Stock']
  filteredLocations.value.forEach(l=>{ csv.push(`${l.nomor_box},${l.nomor_laci},"${l.name}",${l.product_count},${l.total_stock||0}`) })
  const blob = new Blob([csv.join('\n')], {type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='box-laci.csv'; a.click(); URL.revokeObjectURL(url)
}
</script>
