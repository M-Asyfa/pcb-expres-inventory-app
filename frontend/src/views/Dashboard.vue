<template>
  <div>

    <div v-if="loading" class="text-gray-500">Memuat statistik...</div>

    <div v-else>
      <!-- 4 cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
        <div class="relative bg-white rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#4e73df] p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div>
            <div class="text-[10px] font-bold text-[#4e73df] uppercase">Total Stock Inventory</div>
            <div class="text-[22px] font-extrabold text-[#0F172A] mt-1">{{ stats.total_quantity || 0 }}</div>
          </div>
          <div class="text-[#dddfeb] text-2xl">📦</div>
        </div>
        <div class="relative bg-white rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#1cc88a] p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div>
            <div class="text-[10px] font-bold text-[#1cc88a] uppercase">Price Total Stock</div>
            <div class="text-[15px] font-extrabold text-[#0F172A] mt-1">Rp {{ Number(stats.total_value || 0).toLocaleString('id-ID') }}</div>
          </div>
          <div class="text-[#dddfeb] text-2xl">$</div>
        </div>
        <div class="relative bg-white rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#36b9cc] p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div>
            <div class="text-[10px] font-bold text-[#36b9cc] uppercase">Total Category Stock</div>
            <div class="text-[22px] font-extrabold text-[#0F172A] mt-1">{{ stats.category_count || 0 }}</div>
          </div>
          <div class="text-[#dddfeb]">📋</div>
        </div>
        <div class="relative bg-white rounded-[20px] border border-[#F0E6D2] border-l-[4px] border-l-[#f6c23e] p-5 flex justify-between items-center shadow-[0_4px_20px_rgba(0,0,0,0.04)]">
          <div>
            <div class="text-[10px] font-bold text-[#f6c23e] uppercase">This Day In Out Stock</div>
            <div class="text-[14px] font-extrabold text-[#0F172A] mt-1">▲ {{ dayInOut.in }} ▼ {{ dayInOut.out }}</div>
          </div>
          <div class="text-[#dddfeb] text-xl">✔</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Donut -->
        <div class="bg-white rounded-[20px] border border-[#F0E6D2] shadow-[0_4px_20px_rgba(0,0,0,0.04)] overflow-hidden">
          <div class="px-6 py-4 bg-[#FFFBF2] border-b border-[#F0E6D2] flex justify-between items-center">
            <span class="font-bold text-[13px] text-[#0F1E35]">% Total Item dari Kategori</span>
            <span class="text-[10px] text-gray-500">{{ catStats.length }} kategori</span>
          </div>
          <div class="p-6">
            <div v-if="catStats.length===0" class="py-12 text-center">
              <div class="text-gray-400 text-sm">Tidak ada data kategori</div>
              <div class="text-[11px] text-gray-400 mt-2">API: /api/products/stats/categories returned empty</div>
              <button @click="reloadCategories" class="mt-3 px-3 py-1 bg-[#0F1E35] text-white rounded text-xs">Reload</button>
              <div class="mt-4 text-left text-[11px] bg-[#FFFBF2] p-3 rounded border">
                Debug: stats={{ JSON.stringify(stats).slice(0,200) }}
              </div>
            </div>
            <div v-else>
              <div class="flex justify-center">
                <canvas ref="donutCanvas" class="max-w-[320px] max-h-[320px]"></canvas>
              </div>
              <!-- Fallback list with % -->
              <div class="mt-4 grid grid-cols-2 gap-2 text-[11px]">
                <div v-for="(c, i) in catStats.slice(0,12)" :key="c.kategori" class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full inline-block" :style="{background: donutColors[i % donutColors.length]}"></span>
                  <span class="truncate flex-1">{{ c.kategori || '(empty)' }}</span>
                  <span class="font-bold">{{ c.product_count }}</span>
                  <span class="text-gray-400">({{ ((c.product_count/totalCat)*100).toFixed(1) }}%)</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Low stock -->
        <div class="bg-white rounded-[20px] border border-[#F0E6D2] shadow-[0_4px_20px_rgba(0,0,0,0.04)] overflow-hidden">
          <div class="px-6 py-4 bg-[#FFFBF2] border-b border-[#F0E6D2] font-bold text-[13px] text-[#0F1E35]">Stock item dibawah target</div>
          <div class="p-4">
            <div class="flex justify-between items-center mb-3 text-[12px]">
              <div class="flex gap-2 items-center">Show
                <select v-model="lowPerPage" class="border border-[#E8DDC7] rounded px-2 py-1 text-xs bg-white">
                  <option :value="10">10</option><option :value="25">25</option>
                </select> entries
              </div>
              <div class="flex gap-2 items-center">Search: <input v-model="lowSearch" class="border border-[#E8DDC7] rounded px-2 py-1 text-xs w-36" /></div>
            </div>
            <div class="border border-[#F5EFE4] rounded-xl overflow-auto max-h-[320px]">
              <table class="w-full text-[12px]">
                <thead class="bg-[#FFFBF2] sticky top-0"><tr class="border-b"><th class="py-2 px-3 text-left font-bold">ID ▲</th><th class="text-left font-bold">Nama</th><th class="text-right pr-4 font-bold">Banyak ↕</th></tr></thead>
                <tbody>
                  <tr v-for="p in pagedLowStock" :key="p.id" class="border-b last:border-0 hover:bg-[#FFFBF2]">
                    <td class="py-2 px-3 font-mono text-[11px]">{{ p.id }}</td>
                    <td class="py-2 max-w-[180px] truncate">{{ p.nama }}</td>
                    <td class="py-2 pr-4 text-right font-bold">{{ p.stock }}</td>
                  </tr>
                  <tr v-if="filteredLow.length===0"><td colspan="3" class="py-10 text-center text-gray-400">Tidak ada data low stock</td></tr>
                </tbody>
              </table>
            </div>
            <div class="flex justify-between mt-3 text-[11px] text-gray-500">
              <span>Showing {{ pagedLowStock.length }} of {{ filteredLow.length }}</span>
              <div class="flex gap-1"><button @click="lowPage=Math.max(1,lowPage-1)" class="px-2 py-1 border rounded bg-white">Prev</button><span class="px-2 py-1 bg-[#0F1E35] text-white rounded">{{ lowPage }}</span><button @click="lowPage++" class="px-2 py-1 border rounded bg-white">Next</button></div>
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
const sortKey = ref('id')
const sortAsc = ref(true)
const dayInOut = ref({ in: 0, out: 0 })

const donutColors = ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b','#858796','#5a5c69','#2e59d9','#A7F3D0','#BFDBFE','#FDE68A','#DCCEB0']

const filteredLow = computed(()=>{
  let list = lowStock.value
  if (lowSearch.value) {
    const s = lowSearch.value.toLowerCase()
    list = list.filter(p=> p.nama.toLowerCase().includes(s) || String(p.id).includes(s))
  }
  list = [...list].sort((a,b)=>{
    const k = sortKey.value
    if (a[k] < b[k]) return sortAsc.value ? -1 : 1
    if (a[k] > b[k]) return sortAsc.value ? 1 : -1
    return 0
  })
  return list
})

const pagedLowStock = computed(()=>{
  const start = (lowPage.value-1)*lowPerPage.value
  return filteredLow.value.slice(start, start+lowPerPage.value)
})

const totalCat = computed(()=> catStats.value.reduce((a,c)=> a + (parseInt(c.product_count)||0), 0))

watch([lowSearch, lowPerPage], ()=>{ lowPage.value=1 })

const renderDonut = async () => {
  await nextTick()
  const canvas = donutCanvas.value
  if (!canvas) {
    console.warn('donutCanvas ref null')
    return
  }
  if (catStats.value.length===0) {
    console.warn('catStats empty, no chart')
    return
  }
  try {
    const { Chart, ArcElement, Tooltip, Legend, DoughnutController } = await import('chart.js')
    Chart.register(ArcElement, Tooltip, Legend, DoughnutController)
    if (donutChart) {
      donutChart.destroy()
      donutChart = null
    }
    const labels = catStats.value.map(c=> (c.kategori || '(empty)').slice(0,20))
    const data = catStats.value.map(c=> parseInt(c.product_count) || 0)
    console.log('Rendering donut', labels, data)
    donutChart = new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [{
          data,
          backgroundColor: donutColors.slice(0, data.length),
          borderWidth: 2,
          borderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '68%',
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (ctx) => {
                const total = ctx.dataset.data.reduce((a,b)=>a+b,0)
                const pct = total ? ((ctx.parsed/total)*100).toFixed(1) : 0
                return `${ctx.label}: ${ctx.parsed} (${pct}%)`
              }
            }
          }
        }
      }
    })
  } catch (e) {
    console.error('Chart render failed', e)
  }
}

const reloadCategories = async () => {
  try {
    const res = await productService.getCategoriesStats()
    catStats.value = res.data.data
    await nextTick()
    renderDonut()
  } catch (e) {
    console.error(e)
    alert('Gagal load kategori: ' + e.message)
  }
}

onMounted(async () => {
  try {
    const [statsRes, lowRes, catRes, boxRes] = await Promise.all([
      productService.getStats(),
      productService.getLowStock(),
      productService.getCategoriesStats().catch(async ()=>{ 
        // fallback to general stats per kategori from categories endpoint
        try { const r = await productService.getAll({per_page:1}); return {data:{data:[]}} } catch { return {data:{data:[]}} }
      }),
      productService.getBoxesStats()
    ])
    stats.value = statsRes.data.data
    lowStock.value = lowRes.data.data
    catStats.value = catRes.data.data || []
    boxStats.value = boxRes.data.data || []
    console.log('catStats loaded', catStats.value.length, catStats.value.slice(0,3))
    await nextTick()
    setTimeout(renderDonut, 300)
  } catch (e) {
    console.error('Dashboard load error', e)
  } finally {
    loading.value = false
  }
})

watch(catStats, ()=>{ nextTick(()=> setTimeout(renderDonut, 100)) })
</script>
