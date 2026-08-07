<template>
  <div>
    <div v-if="loading" class="text-gray-500 py-12 text-center text-sm">Memuat statistik...</div>

    <div v-else>
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-5 mb-4 lg:mb-6">
        <div class="relative bg-white rounded-[16px] lg:rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#4e73df] p-3 lg:p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div class="min-w-0 flex-1">
            <div class="text-[9px] lg:text-[10px] font-bold text-[#4e73df] uppercase truncate">Total Stock Inventory</div>
            <div class="text-[18px] lg:text-[22px] font-extrabold text-[#0F172A] mt-1">{{ stats.total_quantity || 0 }}</div>
          </div>
          <div class="text-[#dddfeb] text-xl lg:text-2xl">📦</div>
        </div>
        <div class="relative bg-white rounded-[16px] lg:rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#1cc88a] p-3 lg:p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div class="min-w-0 flex-1">
            <div class="text-[9px] lg:text-[10px] font-bold text-[#1cc88a] uppercase truncate">Price Total Stock</div>
            <div class="text-[12px] lg:text-[15px] font-extrabold text-[#0F172A] mt-1 truncate">Rp {{ Number(stats.total_value || 0).toLocaleString('id-ID') }}</div>
          </div>
          <div class="text-[#dddfeb] text-xl lg:text-2xl">$</div>
        </div>
        <div class="relative bg-white rounded-[16px] lg:rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#36b9cc] p-3 lg:p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div>
            <div class="text-[9px] lg:text-[10px] font-bold text-[#36b9cc] uppercase">Total Category</div>
            <div class="text-[18px] lg:text-[22px] font-extrabold text-[#0F172A] mt-1">{{ stats.category_count || 0 }}</div>
          </div>
          <div class="text-[#dddfeb] text-xl">📋</div>
        </div>
        <div class="relative bg-white rounded-[16px] lg:rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#f6c23e] p-3 lg:p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div class="min-w-0 flex-1">
            <div class="text-[9px] lg:text-[10px] font-bold text-[#f6c23e] uppercase truncate">This Day In Out</div>
            <div class="text-[12px] lg:text-[14px] font-extrabold text-[#0F172A] mt-1">▲ {{ dayInOut.in }} ▼ {{ dayInOut.out }}</div>
          </div>
          <div class="text-[#dddfeb] text-xl">✔</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-5">
        <div class="bg-white rounded-[16px] lg:rounded-[20px] border border-[#F0E6D2] shadow-[0_4px_20px_rgba(0,0,0,0.04)] overflow-hidden">
          <div class="px-4 lg:px-6 py-3 lg:py-4 bg-[#FFFBF2] border-b border-[#F0E6D2] flex justify-between items-center">
            <span class="font-bold text-[12px] lg:text-[13px] text-[#0F1E35]">% Total Item dari Kategori</span>
            <span class="text-[10px] text-gray-500">{{ catStats.length }} kategori</span>
          </div>
          <div class="p-4 lg:p-6">
            <div v-if="catStats.length===0" class="py-8 lg:py-12 text-center">
              <div class="text-gray-400 text-sm">Tidak ada data kategori</div>
              <button @click="reloadCategories" class="mt-3 px-4 py-2 bg-[#0F1E35] text-white rounded-[10px] text-xs h-9">Reload</button>
            </div>
            <div v-else>
              <div class="flex justify-center">
                <canvas ref="donutCanvas" class="max-w-[260px] lg:max-w-[320px] max-h-[260px] lg:max-h-[320px] w-full"></canvas>
              </div>
              <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-2 text-[11px]">
                <div v-for="(c, i) in catStats.slice(0,12)" :key="c.kategori" class="flex items-center gap-2 bg-[#FFFBF2] lg:bg-transparent border lg:border-0 border-[#F0E6D2] rounded-[8px] px-2 py-1.5 lg:p-0">
                  <span class="w-2.5 h-2.5 rounded-full inline-block flex-shrink-0" :style="{background: donutColors[i % donutColors.length]}"></span>
                  <span class="truncate flex-1 font-medium">{{ c.kategori || '(empty)' }}</span>
                  <span class="font-bold">{{ c.product_count }}</span>
                  <span class="text-gray-400">({{ ((c.product_count/totalCat)*100).toFixed(1) }}%)</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Low stock with fixed pagination -->
        <div class="bg-white rounded-[16px] lg:rounded-[20px] border border-[#F0E6D2] shadow-[0_4px_20px_rgba(0,0,0,0.04)] overflow-hidden flex flex-col">
          <div class="px-4 lg:px-6 py-3 lg:py-4 bg-[#FFFBF2] border-b border-[#F0E6D2] font-bold text-[12px] lg:text-[13px] text-[#0F1E35] flex justify-between items-center">
            <span>Stock item dibawah target</span>
            <span class="text-[10px] bg-red-100 text-red-700 border border-red-200 px-2 py-0.5 rounded-full">{{ filteredLow.length }} item</span>
          </div>
          <div class="p-3 lg:p-4 flex-1 flex flex-col">
            <div class="flex flex-col lg:flex-row justify-between gap-2 mb-3 text-[11px] lg:text-[12px]">
              <div class="flex gap-2 items-center">Show
                <select v-model="lowPerPage" class="border border-[#E8DDC7] rounded-[8px] px-2 py-1.5 text-xs bg-white h-9">
                  <option :value="10">10</option><option :value="25">25</option><option :value="50">50</option>
                </select> entries
              </div>
              <div class="flex gap-2 items-center flex-1 lg:flex-none">Search: <input v-model="lowSearch" placeholder="Cari ID/Nama..." class="border border-[#E8DDC7] rounded-[8px] px-3 py-1.5 text-xs flex-1 lg:w-44 h-9" /></div>
            </div>

            <div class="border border-[#F5EFE4] rounded-xl overflow-auto flex-1 min-h-[200px] max-h-[360px]">
              <table class="w-full text-[11px] lg:text-[12px]">
                <thead class="bg-[#FFFBF2] sticky top-0 z-10">
                  <tr class="border-b text-[#0F172A]">
                    <th class="py-2.5 px-3 text-left font-bold cursor-pointer whitespace-nowrap hover:bg-[#F3EBD9]" @click="sortByLow('id')">
                      ID <span :class="sortKey==='id' ? 'text-[#0F1E35]' : 'text-[#E8DDC7]'">{{ sortKey==='id' ? (sortAsc ? '▲' : '▼') : '↕' }}</span>
                    </th>
                    <th class="py-2.5 text-left font-bold cursor-pointer whitespace-nowrap hover:bg-[#F3EBD9]" @click="sortByLow('nama')">
                      Nama <span :class="sortKey==='nama' ? 'text-[#0F1E35]' : 'text-[#E8DDC7]'">{{ sortKey==='nama' ? (sortAsc ? '▲' : '▼') : '↕' }}</span>
                    </th>
                    <th class="py-2.5 pr-4 text-right font-bold cursor-pointer whitespace-nowrap hover:bg-[#F3EBD9]" @click="sortByLow('stock')">
                      Banyak <span :class="sortKey==='stock' ? 'text-[#0F1E35]' : 'text-[#E8DDC7]'">{{ sortKey==='stock' ? (sortAsc ? '▲' : '▼') : '↕' }}</span>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="p in pagedLowStock" :key="p.id" class="border-b last:border-0 hover:bg-[#FFFBF2] transition">
                    <td class="py-2.5 px-3 font-mono text-[10px] lg:text-[11px]">{{ p.id }}</td>
                    <td class="py-2.5 max-w-[160px] lg:max-w-[200px] truncate font-medium" :title="p.nama">{{ p.nama }}</td>
                    <td class="py-2.5 pr-4 text-right font-bold text-red-600">{{ p.stock }}</td>
                  </tr>
                  <tr v-if="filteredLow.length===0"><td colspan="3" class="py-12 text-center text-gray-400">Tidak ada data low stock</td></tr>
                </tbody>
              </table>
            </div>

            <!-- Fixed pagination -->
            <div class="flex flex-col lg:flex-row justify-between gap-2 mt-3 text-[11px]">
              <div class="text-gray-500 text-[10px] lg:text-[11px] order-2 lg:order-1">
                Showing {{ showingFrom }} to {{ showingTo }} of {{ filteredLow.length }} • Page {{ lowPage }}/{{ lowTotalPages }}
              </div>
              <div class="flex gap-1 items-center justify-center order-1 lg:order-2 flex-wrap">
                <button @click="goLowPage(1)" :disabled="lowPage<=1" class="px-2 py-1.5 border rounded-[8px] bg-white h-8 disabled:opacity-40">«</button>
                <button @click="goLowPage(lowPage-1)" :disabled="lowPage<=1" class="px-3 py-1.5 border rounded-[8px] bg-white h-8 disabled:opacity-40">‹ Prev</button>
                <template v-for="pg in visibleLowPages" :key="pg">
                  <button v-if="pg!=='...'" @click="goLowPage(pg)" :class="['w-8 h-8 rounded-[8px] text-[11px] font-bold border', pg===lowPage ? 'bg-[#0F1E35] text-white border-[#0F1E35]' : 'bg-white border-[#E8DDC7] hover:bg-[#FFFBF2]']">{{ pg }}</button>
                  <span v-else class="px-1 text-gray-400">...</span>
                </template>
                <button @click="goLowPage(lowPage+1)" :disabled="lowPage>=lowTotalPages" class="px-3 py-1.5 border rounded-[8px] bg-white h-8 disabled:opacity-40">Next ›</button>
                <button @click="goLowPage(lowTotalPages)" :disabled="lowPage>=lowTotalPages" class="px-2 py-1.5 border rounded-[8px] bg-white h-8 disabled:opacity-40">»</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { productService } from '../services/api'

const stats = ref({})
const lowStock = ref([])
const catStats = ref([])
const boxStats = ref([])
const loading = ref(true)
const donutCanvas = ref(null)
let donutChart = null

const lowSearch = ref('')
const lowPerPage = ref(10)
const lowPage = ref(1)
const sortKey = ref('stock')
const sortAsc = ref(true)
const dayInOut = ref({ in: 0, out: 0 })

const donutColors = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#5a5c69','#2e59d9','#A7F3D0','#BFDBFE','#FDE68A','#DCCEB0']

const filteredLow = computed(()=>{
  let list = lowStock.value
  if (lowSearch.value) {
    const s = lowSearch.value.toLowerCase()
    list = list.filter(p=> (p.nama||'').toLowerCase().includes(s) || String(p.id).toLowerCase().includes(s))
  }
  list = [...list].sort((a,b)=>{
    const k = sortKey.value
    let av = a[k], bv = b[k]
    if (k==='nama') {
      av = (av||'').toLowerCase(); bv = (bv||'').toLowerCase()
      return sortAsc.value ? av.localeCompare(bv) : bv.localeCompare(av)
    }
    // numeric
    av = Number(av) || 0; bv = Number(bv) || 0
    return sortAsc.value ? av-bv : bv-av
  })
  return list
})

const lowTotalPages = computed(()=> Math.max(1, Math.ceil(filteredLow.value.length / lowPerPage.value)))

const pagedLowStock = computed(()=>{
  const start = (lowPage.value-1)*lowPerPage.value
  return filteredLow.value.slice(start, start+lowPerPage.value)
})

const showingFrom = computed(()=> filteredLow.value.length===0 ? 0 : (lowPage.value-1)*lowPerPage.value + 1)
const showingTo = computed(()=> Math.min(lowPage.value*lowPerPage.value, filteredLow.value.length))

const visibleLowPages = computed(()=>{
  const total = lowTotalPages.value, cur = lowPage.value
  if (total <= 5) return Array.from({length: total}, (_,i)=>i+1)
  const pages = [1]
  if (cur > 3) pages.push('...')
  for (let i=Math.max(2, cur-1); i<=Math.min(total-1, cur+1); i++) pages.push(i)
  if (cur < total-2) pages.push('...')
  pages.push(total)
  return pages
})

const totalCat = computed(()=> catStats.value.reduce((a,c)=> a + (parseInt(c.product_count)||0), 0))

watch([lowSearch, lowPerPage], ()=>{
  lowPage.value = 1
})

watch(lowTotalPages, (newTotal)=>{
  if (lowPage.value > newTotal) lowPage.value = Math.max(1, newTotal)
})

const goLowPage = (p) => {
  if (p === '...') return
  const total = lowTotalPages.value
  if (p < 1) p = 1
  if (p > total) p = total
  lowPage.value = p
}

const sortByLow = (k) => {
  if (sortKey.value === k) sortAsc.value = !sortAsc.value
  else { sortKey.value = k; sortAsc.value = k==='nama' ? true : false }
}

const renderDonut = async () => {
  await nextTick()
  const canvas = donutCanvas.value
  if (!canvas || catStats.value.length===0) return
  try {
    const { Chart, ArcElement, Tooltip, Legend, DoughnutController } = await import('chart.js')
    Chart.register(ArcElement, Tooltip, Legend, DoughnutController)
    if (donutChart) { donutChart.destroy(); donutChart = null }
    const labels = catStats.value.map(c=> (c.kategori || '(empty)').slice(0,20))
    const data = catStats.value.map(c=> parseInt(c.product_count) || 0)
    donutChart = new Chart(canvas, {
      type: 'doughnut',
      data: { labels, datasets: [{ data, backgroundColor: donutColors.slice(0, data.length), borderWidth: 2, borderColor: '#fff' }] },
      options: { responsive: true, maintainAspectRatio: true, cutout: '68%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => { const total = ctx.dataset.data.reduce((a,b)=>a+b,0); const pct = total ? ((ctx.parsed/total)*100).toFixed(1) : 0; return `${ctx.label}: ${ctx.parsed} (${pct}%)` } } } } }
    })
  } catch (e) { console.error(e) }
}

const reloadCategories = async () => {
  try {
    const res = await productService.getCategoriesStats()
    catStats.value = res.data.data
    await nextTick(); renderDonut()
  } catch (e) { alert('Gagal load kategori: ' + e.message) }
}

onMounted(async () => {
  try {
    const [statsRes, lowRes, catRes, boxRes, logsRes] = await Promise.all([
      productService.getStats(),
      productService.getLowStock(),
      productService.getCategoriesStats().catch(()=> ({data:{data:[]}})),
      productService.getBoxesStats(),
      productService.getLogs().catch(()=> ({data:{data:[]}}))
    ])
    stats.value = statsRes.data.data
    lowStock.value = lowRes.data.data || []
    catStats.value = catRes.data.data || []
    boxStats.value = boxRes.data.data || []
    // Calculate day in/out from logs
    try {
      const logs = logsRes.data.data || []
      const today = new Date().toISOString().slice(0,10)
      let inC=0, outC=0
      logs.forEach(l=>{
        const w = String(l.waktu||'').slice(0,10)
        if (w !== today) return
        if (Number(l.stock) > 0) inC++
        else if (Number(l.stock) < 0) outC++
      })
      dayInOut.value = { in: inC, out: outC }
    } catch {}
    await nextTick(); setTimeout(renderDonut, 300)
  } catch (e) { console.error(e) } finally { loading.value = false }
})

watch(catStats, ()=>{ nextTick(()=> setTimeout(renderDonut, 100)) })
</script>
