<template>
  <div class="min-h-screen flex bg-[#FAF6EE]">
    <!-- Sidebar - cleaned, no dark card, no ARWT/ATAB -->
    <aside class="w-[280px] bg-[#F6EFE0]/60 border-r border-[#E8DDC7] p-4 flex flex-col gap-5">
      <!-- Nav -->
      <nav class="flex-1 space-y-1.5">
        <router-link to="/" custom v-slot="{ isActive, navigate }">
          <div @click="navigate" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60']">
            <div class="text-[13px] font-bold" :class="isActive ? 'text-[#0F1E35]' : 'text-[#0F1E35]/80'">Dashboard</div>
            <div class="text-[11px] text-gray-500 mt-0.5">Overview dan ringkasan sistem</div>
          </div>
        </router-link>

        <router-link to="/products" custom v-slot="{ isActive, navigate }">
          <div @click="navigate" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60']">
            <div class="text-[13px] font-bold" :class="isActive ? 'text-[#0F1E35]' : 'text-[#0F1E35]/80'">Data Barang</div>
            <div class="text-[11px] text-gray-500 mt-0.5">{{ totalInfo }}</div>
          </div>
        </router-link>

        <router-link to="/categories" custom v-slot="{ isActive, navigate }">
          <div @click="navigate" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60']">
            <div class="text-[13px] font-bold">Kategori</div>
            <div class="text-[11px] text-gray-500 mt-0.5">Pengelompokan komponen</div>
          </div>
        </router-link>

        <router-link to="/locations" custom v-slot="{ isActive, navigate }">
          <div @click="navigate" :class="['cursor-pointer rounded-[14px] px-4 py-3 transition-all', isActive ? 'bg-white shadow-[0_4px_16px_rgba(0,0,0,0.06)] border border-[#E8DDC7]' : 'hover:bg-white/60']">
            <div class="text-[13px] font-bold">Box / Laci</div>
            <div class="text-[11px] text-gray-500 mt-0.5">Lokasi fisik gudang</div>
          </div>
        </router-link>
      </nav>

      <div class="px-4 pb-2 text-[10px] text-gray-400">
        © PCB Expres Jogja • {{ new Date().getFullYear() }}
      </div>
    </aside>

    <!-- Main Content - cleaned, no operator workspace bar -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Page Content -->
      <main class="flex-1 px-6 py-6 overflow-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { productService } from './services/api'

const totalInfo = ref('Memuat...')
onMounted(async () => {
  try {
    const res = await productService.getStats()
    const s = res.data.data
    totalInfo.value = `${s.total_products} jenis • ${s.total_quantity} pcs`
  } catch {
    totalInfo.value = 'Data barang inventory'
  }
})
</script>
