<template>
  <div>
    <div class="mb-5">
      <div class="text-[10px] tracking-[0.2em] text-gray-500 font-semibold">CATEGORY OVERVIEW</div>
      <h1 class="text-[32px] font-extrabold tracking-tight leading-none mt-1">Kategori</h1>
      <p class="text-[12px] text-gray-500 mt-1">Pengelompokan komponen berdasarkan jenis di inventory_pcbexpressjogja</p>
    </div>

    <Card>
      <CardHeader class="flex flex-row justify-between items-center">
        <div>
          <CardTitle>Semua Kategori ({{ categories.length }})</CardTitle>
          <CardDescription>Total jenis, stock, nilai per kategori</CardDescription>
        </div>
        <UiButton @click="showForm=true">+ Tambah Kategori</UiButton>
      </CardHeader>
      <CardContent class="p-0 overflow-x-auto">
        <table class="w-full text-[12px]">
          <thead class="border-b border-[var(--color-border)] bg-[#FFFBF2] text-[10px] tracking-[0.14em] text-gray-500 font-semibold">
            <tr><th class="py-3 px-6 text-left">Kategori</th><th>Jumlah Jenis</th><th>Total Stock</th><th>Total Nilai</th><th class="pr-6">Aksi</th></tr>
          </thead>
          <tbody>
            <tr v-for="c in categories" :key="c.kategori" class="border-b border-[#F5EFE4] hover:bg-[#FFFBF2]">
              <td class="py-3 px-6"><Badge variant="blue">{{ c.kategori || '(empty)' }}</Badge></td>
              <td>{{ c.product_count }}</td>
              <td>{{ c.total_stock }}</td>
              <td class="font-mono">{{ c.total_value ? 'Rp'+Number(c.total_value).toLocaleString('id-ID') : '-' }}</td>
              <td class="pr-6 flex gap-2"><button @click="edit(c)" class="text-[11px] font-semibold hover:underline">Edit</button><button @click="remove(c.kategori)" class="text-[11px] text-red-600 hover:underline">Delete</button></td>
            </tr>
          </tbody>
        </table>
      </CardContent>
    </Card>

    <div v-if="showForm" class="fixed inset-0 bg-[#0F1E35]/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
      <Card class="w-full max-w-md">
        <CardHeader><CardTitle>{{ editing ? 'Edit' : 'Tambah' }} Kategori</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="save" class="space-y-3">
            <UiInput v-model="form.name" placeholder="Nama kategori e.g. RESISTOR" />
            <p class="text-[11px] text-gray-500">Disimpan di tabel kategori dan dipakai di data_barang.kategori</p>
            <div class="flex justify-end gap-2"><UiButton variant="secondary" @click="close">Batal</UiButton><UiButton type="submit">Simpan</UiButton></div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
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
const form = reactive({ name:'' })
const fetch = async () => { try{ const r=await categoryService.getStats(); categories.value=r.data.data } catch{ const r=await categoryService.getAll(); categories.value=r.data.data } }
onMounted(fetch)
const close = () => { showForm.value=false; editing.value=null; form.name='' }
const save = async () => { try{ if(editing.value) await categoryService.update(editing.value.kategori,{name:form.name}); else await categoryService.create({name:form.name}); close(); fetch() } catch(e){ alert(e.response?.data?.error||e.message) } }
const edit = (c)=>{ editing.value=c; form.name=c.kategori; showForm.value=true }
const remove = async (id)=>{ if(confirm(`Hapus kategori ${id}?`)){ try{ await categoryService.delete(id); fetch() } catch(e){ alert(e.response?.data?.error||e.message) } } }
</script>
