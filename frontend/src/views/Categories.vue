<template>
  <div>
    <div class="mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">CATEGORY OVERVIEW</div>
      <div class="flex justify-between items-end flex-wrap gap-3">
        <div>
          <h1 class="text-[32px] font-extrabold tracking-tight leading-none mt-1">Kategori</h1>
          <p class="text-[12px] text-gray-500 mt-1">Pengelompokan komponen • {{ filtered.length }} kategori • {{ totalJenis }} jenis total</p>
        </div>
        <UiButton @click="showForm=true">+ Tambah Kategori</UiButton>
      </div>
    </div>

    <Card class="mb-5">
      <CardContent class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex gap-2 items-center">
          <UiInput v-model="search" placeholder="Cari kategori..." class="w-[260px]" />
          <span class="text-[11px] text-gray-500">{{ filtered.length }} dari {{ categories.length }}</span>
        </div>
        <div class="flex gap-2">
          <UiButton variant="secondary" @click="exportCsv">📥 Export CSV</UiButton>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[11px]">
          <thead class="border-b border-[#E8DDC7] bg-[#FFFBF2]">
            <tr class="text-[11px] font-bold text-[#0F172A] text-left tracking-wide">
              <th class="py-3 px-4 cursor-pointer whitespace-nowrap" @click="sortBy('kategori')">Kategori <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('product_count')">Jumlah Jenis <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('total_stock')">Total Stock <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 cursor-pointer whitespace-nowrap" @click="sortBy('total_value')">Total Nilai (Rp) <span class="text-[#E8DDC7]">↕</span></th>
              <th class="py-3 pr-4 whitespace-nowrap">Aksi <span class="text-[#E8DDC7]">↕</span></th>
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

    <div v-if="showForm" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <Card class="w-full max-w-md">
        <CardHeader><CardTitle>{{ editing ? 'Edit Kategori' : 'Tambah Kategori' }}</CardTitle><CardDescription>Disimpan di tabel kategori</CardDescription></CardHeader>
        <CardContent>
          <form @submit.prevent="save" class="space-y-3">
            <div><label class="text-[11px] font-bold">Nama Kategori *</label><UiInput v-model="form.name" placeholder="RESISTOR, KAPASITOR, IC..." /></div>
            <p class="text-[11px] text-gray-500">Akan dipakai di data_barang.kategori untuk filter</p>
            <div class="flex justify-end gap-2 pt-2"><UiButton variant="secondary" @click="close">Batal</UiButton><UiButton type="submit">Simpan</UiButton></div>
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
const remove = async (id)=>{ if(confirm(`Hapus kategori ${id}? Barang tetap ada tapi kategori jadi kosong.`)){ try{ await categoryService.delete(id); fetch() } catch(e){ alert(e.response?.data?.error||e.message) } } }

const exportCsv = () => {
  const csv = ['kategori,product_count,total_stock,total_value']
  filtered.value.forEach(c=>{ csv.push(`"${c.kategori}",${c.product_count},${c.total_stock},${c.total_value||0}`) })
  const blob = new Blob([csv.join('\n')], {type:'text/csv'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='kategori.csv'; a.click(); URL.revokeObjectURL(url)
}
</script>
