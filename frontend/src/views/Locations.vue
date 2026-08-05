<template>
  <div>
    <div class="mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">LOCATION OVERVIEW</div>
      <div class="flex justify-between items-end flex-wrap gap-3">
        <div>
          <h1 class="text-[32px] font-extrabold tracking-tight leading-none mt-1">Box / Laci</h1>
          <p class="text-[12px] text-gray-500 mt-1">{{ filteredLocations.length }} dari {{ locations.length }} lokasi • Box-Laci tracking fisik gudang</p>
        </div>
        <UiButton variant="secondary" @click="exportLocationsCsv">📥 Export CSV</UiButton>
      </div>
    </div>

    <Card class="mb-4">
      <CardContent class="flex flex-wrap gap-3 items-center">
        <UiInput v-model="search" placeholder="Cari Box / Laci..." class="max-w-sm" />
        <select v-model="boxFilter" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option value="">Semua Box</option>
          <option v-for="b in boxes" :key="b.nomor_box" :value="b.nomor_box">Box {{ b.nomor_box }}</option>
        </select>
        <select v-model="perPage" class="h-10 rounded-[12px] border border-[var(--color-border)] bg-white px-3 text-sm">
          <option :value="20">20 / hal</option><option :value="50">50 / hal</option><option :value="100">100 / hal</option>
        </select>
        <span class="text-[11px] text-gray-500">{{ currentPage }} / {{ totalPages }} hal</span>
      </CardContent>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
      <Card class="lg:col-span-2">
        <CardHeader><CardTitle>Daftar Lokasi</CardTitle><CardDescription>Kombinasi unik Box-Laci yang terpakai</CardDescription></CardHeader>
        <CardContent class="p-0 overflow-x-auto">
          <table class="w-full text-[12px]">
            <thead class="border-b border-[var(--color-border)] bg-[#FFFBF2] text-[10px] tracking-[0.14em] text-gray-500 font-semibold">
              <tr><th class="py-3 px-6 text-left">Box</th><th>Laci</th><th>Nama</th><th>Jenis</th><th>Stock</th><th class="pr-6">Aksi</th></tr>
            </thead>
            <tbody>
              <tr v-for="l in pagedLocations" :key="l.id" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2]">
                <td class="py-3 px-6 font-mono">Box {{ l.nomor_box }}</td>
                <td>Laci {{ l.nomor_laci }}</td>
                <td class="font-semibold">{{ l.name }}</td>
                <td><Badge variant="secondary">{{ l.product_count }} jenis</Badge></td>
                <td>{{ l.total_stock }}</td>
                <td class="pr-6"><button @click="viewProducts(l)" class="text-[11px] font-bold hover:underline">View</button></td>
              </tr>
            </tbody>
          </table>
          <div class="flex justify-between p-4">
            <UiButton variant="secondary" size="sm" @click="currentPage=Math.max(1,currentPage-1)" :disabled="currentPage<=1">Prev</UiButton>
            <span class="text-[11px]">Page {{ currentPage }} of {{ totalPages }}</span>
            <UiButton variant="secondary" size="sm" @click="currentPage=Math.min(totalPages,currentPage+1)" :disabled="currentPage>=totalPages">Next</UiButton>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader><CardTitle>Box Summary</CardTitle><CardDescription>{{ boxes.length }} box fisik</CardDescription></CardHeader>
        <CardContent class="p-0">
          <table class="w-full text-[12px]">
            <thead class="border-b bg-[#FFFBF2] text-[10px] text-gray-500"><tr><th class="py-2 px-4 text-left">Box</th><th>Laci Count</th><th>Jenis</th><th></th></tr></thead>
            <tbody>
              <tr v-for="b in boxes" :key="b.nomor_box" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2]">
                <td class="py-2 px-4 font-bold">Box {{ b.nomor_box }}</td>
                <td>{{ getLaciCount(b.nomor_box) }}</td>
                <td>{{ b.product_count }}</td>
                <td class="pr-4"><button @click="filterByBox(b.nomor_box)" class="text-[11px] text-[#0F1E35] font-semibold">Filter</button></td>
              </tr>
            </tbody>
          </table>
        </CardContent>
      </Card>
    </div>

    <Card v-if="selectedLocation" class="mt-5">
      <CardHeader class="flex flex-row justify-between items-center">
        <div><CardTitle>Barang di {{ selectedLocation.name }}</CardTitle><CardDescription>{{ selectedProducts.length }} items</CardDescription></div>
        <UiButton variant="secondary" size="sm" @click="selectedLocation=null">Close</UiButton>
      </CardHeader>
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[12px]"><thead class="border-b bg-[#FFFBF2] text-[10px] text-gray-500"><tr><th class="py-2 px-4 text-left">ID</th><th class="text-left">Nama</th><th class="text-left">Kategori</th><th class="text-left">Stock</th><th class="text-left">Harga</th></tr></thead>
        <tbody>
          <tr v-for="p in selectedProducts" :key="p.id" class="border-b hover:bg-[#FFFBF2]"><td class="px-4 py-2 font-mono">{{ p.id }}</td><td class="font-semibold">{{ p.nama }}</td><td><Badge variant="blue">{{ p.kategori }}</Badge></td><td :class="p.stock<=p.batas_stock?'text-red-600 font-bold':''">{{ p.stock }}</td><td>Rp {{ Number(p.harga).toLocaleString('id-ID') }}</td></tr>
        </tbody></table>
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
  return list
})
const totalPages = computed(()=> Math.max(1, Math.ceil(filteredLocations.value.length / perPage.value)))
const pagedLocations = computed(()=> { const start=(currentPage.value-1)*perPage.value; return filteredLocations.value.slice(start,start+perPage.value) })
const getLaciCount = (box) => locations.value.filter(l=> String(l.nomor_box)===String(box)).length
const viewProducts = async (loc) => {
  selectedLocation.value = loc
  const res = await locationService.getProducts(loc.nomor_box, loc.nomor_laci)
  selectedProducts.value = res.data.data
}
const filterByBox = (box)=>{ boxFilter.value=String(box); currentPage.value=1 }
const exportLocationsCsv = () => {
  const csv = ['nomor_box,nomor_laci,name,product_count,total_stock']
  filteredLocations.value.forEach(l=>{ csv.push(`${l.nomor_box},${l.nomor_laci},"${l.name}",${l.product_count},${l.total_stock||0}`) })
  const blob = new Blob([csv.join('\n')], {type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='locations.csv'; a.click(); URL.revokeObjectURL(url)
}
</script>
