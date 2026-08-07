<template>
  <div>
    <div class="mb-4 lg:mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">CATEGORY OVERVIEW</div>
      <div class="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-3">
        <div>
          <h1 class="text-[24px] lg:text-[32px] font-extrabold tracking-tight leading-none mt-1">Kategori</h1>
          <p class="text-[11px] lg:text-[12px] text-gray-500 mt-1">{{ filtered.length }} kategori • {{ totalJenis }} jenis total</p>
        </div>
        <UiButton @click="showForm=true" class="h-11 lg:h-10 w-full lg:w-auto">+ Tambah Kategori</UiButton>
      </div>
    </div>

    <Card class="mb-4 lg:mb-5">
      <CardContent class="p-3 lg:p-4 flex flex-col lg:flex-row gap-3 lg:items-center justify-between">
        <div class="flex gap-2 items-center w-full lg:w-auto">
          <UiInput v-model="search" placeholder="Cari kategori..." class="w-full lg:w-[260px] h-11 lg:h-10 text-[14px]" />
          <span class="text-[11px] text-gray-500 hidden lg:inline">{{ filtered.length }} dari {{ categories.length }}</span>
        </div>
        <div class="flex gap-2 w-full lg:w-auto">
          <UiButton variant="secondary" @click="exportCsv" class="flex-1 lg:flex-none h-11 lg:h-10 text-[12px]">📥 Export CSV</UiButton>
          <div class="lg:hidden text-[11px] text-gray-500 flex items-center px-2">{{ filtered.length }} kategori</div>
        </div>
      </CardContent>
    </Card>

    <!-- Desktop table -->
    <Card class="hidden lg:block">
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[11px]">
          <thead class="border-b border-[#E8DDC7] bg-[#FFFBF2]">
            <tr class="text-[11px] font-bold text-[#0F172A] text-left tracking-wide">
              <th class="py-3 px-4 cursor-pointer whitespace-nowrap" @click="sortBy('kategori')">Kategori ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('product_count')">Jumlah Jenis ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('total_stock')">Total Stock ↕</th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('total_value')">Total Nilai (Rp) ↕</th>
              <th class="py-3 pr-4 whitespace-nowrap">Aksi ↕</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in sorted" :key="c.kategori" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2] transition">
              <td class="py-3 px-4"><Badge variant="blue">{{ c.kategori || '(empty)' }}</Badge></td>
              <td class="font-bold text-center">{{ c.product_count }}</td>
              <td class="text-center">{{ c.total_stock }}</td>
              <td class="font-mono font-bold">{{ c.total_value ? 'Rp'+Number(c.total_value).toLocaleString('id-ID') : '-' }}</td>
              <td class="pr-4">
                <div class="flex gap-1.5">
                  <UiButton @click="edit(c)" variant="secondary" size="sm" class="h-[28px] w-[52px] rounded-[8px]">Edit</UiButton>
                  <UiButton @click="remove(c.kategori)" variant="secondary" size="sm" class="h-[28px] w-[52px] rounded-[8px] hover:bg-red-50 hover:text-red-600">Hapus</UiButton>
                </div>
              </td>
            </tr>
            <tr v-if="filtered.length===0"><td colspan="5" class="py-12 text-center text-gray-400 text-[12px]">Tidak ada data</td></tr>
          </tbody>
        </table>
      </CardContent>
    </Card>

    <!-- Mobile cards -->
    <div class="lg:hidden space-y-2">
      <div v-for="c in sorted" :key="c.kategori" class="bg-white border border-[#F0E6D2] rounded-[16px] p-3 flex justify-between items-center shadow-[0_2px_8px_rgba(0,0,0,0.03)]">
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <Badge variant="blue" class="text-[11px]">{{ c.kategori || '(empty)' }}</Badge>
            <span class="text-[10px] px-2 py-0.5 bg-[#FFFBF2] border border-[#F0E6D2] rounded-full">{{ c.product_count }} jenis</span>
          </div>
          <div class="mt-2 flex gap-3 text-[11px]">
            <span><span class="text-gray-500">Stok:</span> <span class="font-bold">{{ c.total_stock }}</span></span>
            <span class="font-mono"><span class="text-gray-500">Nilai:</span> <span class="font-bold">{{ c.total_value ? 'Rp'+Number(c.total_value).toLocaleString('id-ID') : '-' }}</span></span>
          </div>
        </div>
        <div class="flex gap-1.5 ml-2">
          <button @click="edit(c)" class="w-9 h-9 bg-[#FFFBF2] border border-[#E8DDC7] rounded-[10px]">✏️</button>
          <button @click="remove(c.kategori)" class="w-9 h-9 bg-white border border-[#E8DDC7] rounded-[10px]">🗑️</button>
        </div>
      </div>
      <div v-if="filtered.length===0" class="py-12 text-center text-gray-400 text-[12px] bg-white rounded-[16px] border border-[#F0E6D2]">Tidak ada data</div>
    </div>

    <div v-if="showForm" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-end lg:items-center justify-center p-0 lg:p-4 z-50">
      <Card class="w-full max-w-md rounded-t-[20px] lg:rounded-[20px] max-h-[90vh] overflow-auto">
        <CardHeader class="sticky top-0 bg-white border-b border-[#F0E6D2] lg:border-0"><CardTitle class="text-[15px]">{{ editing ? 'Edit Kategori' : 'Tambah Kategori' }}</CardTitle><CardDescription>Disimpan di tabel kategori</CardDescription></CardHeader>
        <CardContent class="p-4">
          <form @submit.prevent="save" class="space-y-3">
            <div><label class="text-[11px] font-bold">Nama Kategori *</label><UiInput v-model="form.name" placeholder="RESISTOR, KAPASITOR, IC..." class="h-11 text-[14px]" /></div>
            <p class="text-[11px] text-gray-500">Akan dipakai di data_barang.kategori untuk filter</p>
            <div class="flex justify-end gap-2 pt-2 sticky bottom-0 bg-white py-2"><UiButton variant="secondary" @click="close" type="button" class="h-11 flex-1">Batal</UiButton><UiButton type="submit" class="h-11 flex-1">Simpan</UiButton></div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { categoryService } from '../services/api'
import Card from '../components/ui/Card.vue'
import CardHeader from '../components/ui/CardHeader.vue'
import CardTitle from '../components/ui/CardTitle.vue'
import CardDescription from '../components/ui/CardDescription.vue'
import CardContent from '../components/ui/CardContent.vue'
import Badge from '../components/ui/Badge.vue'
import UiButton from '../components/ui/Button.vue'
import UiInput from '../components/ui/Input.vue'

const categories = ref([])
const showForm = ref(false)
const editing = ref(null)
const search = ref('')
const sortKey = ref('product_count')
const sortAsc = ref(false)
const form = reactive({ name:'' })

const fetch = async () => { try{ const r=await categoryService.getStats(); categories.value=r.data.data } catch{ const r=await categoryService.getAll(); categories.value=r.data.data } }
onMounted(fetch)

const filtered = computed(()=>{
  let list = categories.value
  if (search.value) {
    const s = search.value.toLowerCase()
    list = list.filter(c=> String(c.kategori).toLowerCase().includes(s))
  }
  return list
})

const sorted = computed(()=>{
  let list = [...filtered.value]
  list.sort((a,b)=>{
    const k = sortKey.value
    const av = a[k]||0, bv = b[k]||0
    if (k==='kategori') return sortAsc.value ? String(av).localeCompare(String(bv)) : String(bv).localeCompare(String(av))
    return sortAsc.value ? av-bv : bv-av
  })
  return list
})

const totalJenis = computed(()=> categories.value.reduce((s,c)=> s + (parseInt(c.product_count)||0), 0))
const sortBy = (k)=>{ if(sortKey.value===k) sortAsc.value=!sortAsc.value; else { sortKey.value=k; sortAsc.value=true } }
const close = () => { showForm.value=false; editing.value=null; form.name='' }
const save = async () => { try{ if(editing.value) await categoryService.update(editing.value.kategori,{name:form.name}); else await categoryService.create({name:form.name}); close(); fetch() } catch(e){ alert(e.response?.data?.error||e.message) } }
const edit = (c)=>{ editing.value=c; form.name=c.kategori; showForm.value=true }
const remove = async (id)=>{ if(confirm(`Hapus kategori ${id}? Barang tetap ada tapi kategori jadi Lain-lain.`)){ try{ await categoryService.delete(id); fetch() } catch(e){ alert(e.response?.data?.error||e.message) } } }
const exportCsv = () => {
  const csv = ['kategori,product_count,total_stock,total_value']
  filtered.value.forEach(c=>{ csv.push(`"${c.kategori}",${c.product_count},${c.total_stock},${c.total_value||0}`) })
  const blob = new Blob([csv.join('\n')], {type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='kategori.csv'; a.click(); URL.revokeObjectURL(url)
}
</script>
