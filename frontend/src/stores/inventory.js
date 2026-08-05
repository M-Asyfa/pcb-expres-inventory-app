import { defineStore } from 'pinia'
import { ref } from 'vue'
import { productService } from '../services/api'

export const useInventoryStore = defineStore('inventory', () => {
  const stats = ref({})
  const lowStock = ref([])

  async function loadStats() {
    const res = await productService.getStats()
    stats.value = res.data.data
  }
  async function loadLowStock() {
    const res = await productService.getLowStock()
    lowStock.value = res.data.data
  }
  return { stats, lowStock, loadStats, loadLowStock }
})
