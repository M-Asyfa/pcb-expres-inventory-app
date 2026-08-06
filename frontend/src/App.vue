<template>
  <div class="min-h-screen flex bg-[#FAF6EE]">
    <!-- Mobile Top Bar -->
    <div class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-[#F6EFE0] border-b border-[#E8DDC7] px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="w-9 h-9 rounded-[10px] bg-white border border-[#E8DDC7] flex items-center justify-center text-[#0F1E35] shadow-sm">
          <svg v-if="!sidebarOpen" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div>
          <div class="text-[12px] font-extrabold tracking-tight leading-none">PCB Expres Jogja</div>
          <div class="text-[10px] text-gray-500">Inventory • {{ totalInfo }}</div>
        </div>
      </div>
      <div class="text-[10px] px-2 py-1 bg-white border border-[#E8DDC7] rounded-full font-bold text-[#0F1E35]">{{ currentRouteName }}</div>
    </div>

    <!-- Backdrop for mobile -->
    <div v-if="sidebarOpen" class="lg:hidden fixed inset-0 z-30 bg-[#0F1E35]/40 backdrop-blur-sm" @click="sidebarOpen=false"></div>

    <!-- Sidebar -->
    <aside :class="[
      'bg-[#F6EFE0]/80 backdrop-blur-md lg:bg-[#F6EFE0]/60 border-r border-[#E8DDC7] p-4 flex flex-col gap-5 z-40 transition-transform duration-200 ease-out',
      'fixed lg:static top-0 left-0 h-full lg:h-auto w-[280px] lg:w-[280px]',
      sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
      'lg:flex'
    ]">
      <!-- Mobile header inside sidebar -->
      <div class="lg:hidden pt-2 pb-1 border-b border-[#E8DDC7]/60 mb-2">
        <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">SCADA PLATFORM</div>
        <div class="text-[18px] font-extrabold leading-none mt-1">PCB Expres Jogja</div>
        <div class="text-[11px] text-gray-600 mt-1">Satu tempat untuk memantau perangkat, alert, dan kondisi lapangan.</div>
      </div>

      <nav class="flex-1 space-y-1.5 mt-2 lg:mt-0">
        <router-link to="/" custom v-slot="{ isActive, navigate }">
          <div @click="goNav(navigate)" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60 border border-transparent']">
            <div class="text-[13px] font-bold flex items-center gap-2" :class="isActive ? 'text-[#0F1E35]' : 'text-[#0F1E35]/80'">
              <span class="text-[16px]">📊</span> Dashboard
            </div>
            <div class="text-[11px] text-gray-500 mt-0.5">Overview dan ringkasan sistem</div>
          </div>
        </router-link>

        <router-link to="/products" custom v-slot="{ isActive, navigate }">
          <div @click="goNav(navigate)" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60 border border-transparent']">
            <div class="text-[13px] font-bold flex items-center gap-2" :class="isActive ? 'text-[#0F1E35]' : 'text-[#0F1E35]/80'">
              <span class="text-[16px]">📦</span> Data Barang
            </div>
            <div class="text-[11px] text-gray-500 mt-0.5">{{ totalInfo }}</div>
          </div>
        </router-link>

        <router-link to="/categories" custom v-slot="{ isActive, navigate }">
          <div @click="goNav(navigate)" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60 border border-transparent']">
            <div class="text-[13px] font-bold flex items-center gap-2">🏷️ Kategori</div>
            <div class="text-[11px] text-gray-500 mt-0.5">Pengelompokan komponen</div>
          </div>
        </router-link>

        <router-link to="/locations" custom v-slot="{ isActive, navigate }">
          <div @click="goNav(navigate)" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60 border border-transparent']">
            <div class="text-[13px] font-bold flex items-center gap-2">🗄️ Box / Laci</div>
            <div class="text-[11px] text-gray-500 mt-0.5">Lokasi fisik gudang</div>
          </div>
        </router-link>
      </nav>

      <div class="px-4 pb-2 text-[10px] text-gray-400 border-t border-[#E8DDC7]/50 pt-3 mt-auto hidden lg:block">
        © PCB Expres Jogja • {{ new Date().getFullYear() }}
      </div>
      <!-- Mobile footer quick actions -->
      <div class="lg:hidden px-1 pb-2">
        <div class="text-[10px] text-gray-400">© PCB Expres Jogja • {{ new Date().getFullYear() }}</div>
        <div class="mt-3 flex gap-2">
          <a href="http://localhost:8000/api/health" target="_blank" class="flex-1 text-center text-[10px] py-2 bg-white border border-[#E8DDC7] rounded-[10px] font-bold">API Health</a>
          <button @click="sidebarOpen=false" class="flex-1 text-[10px] py-2 bg-[#0F1E35] text-white rounded-[10px] font-bold">Tutup</button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col min-w-0 lg:ml-0">
      <!-- Spacer for fixed mobile top bar -->
      <div class="lg:hidden h-[56px]"></div>
      <main class="flex-1 px-4 lg:px-6 py-4 lg:py-6 overflow-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { productService } from './services/api'

const totalInfo = ref('Memuat...')
const sidebarOpen = ref(false)
const route = useRoute()
const router = useRouter()

const currentRouteName = computed(() => {
  const map = { Dashboard: 'Dashboard', Products: 'Barang', Categories: 'Kategori', Locations: 'Box' }
  return map[route.name] || route.name || 'Menu'
})

const goNav = (navigate) => {
  navigate()
  sidebarOpen.value = false
}

onMounted(async () => {
  try {
    const res = await productService.getStats()
    const s = res.data.data
    totalInfo.value = `${s.total_products} jenis • ${s.total_quantity} pcs`
  } catch {
    totalInfo.value = 'Data barang inventory'
  }
})

// Close sidebar on route change (mobile)
router.afterEach(() => { sidebarOpen.value = false })
</script>
