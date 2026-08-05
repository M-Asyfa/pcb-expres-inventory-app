<template>
  <span :class="['pill', variantClass]"><slot /></span>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ variant: { type: String, default: 'region' } })
const variantClass = computed(()=>{
  const map = {
    region: 'pill-region bg-white border-[#E8DDC7] text-[#0F1E35]',
    role: 'pill-role bg-[#DBEAFE] border-[#BFDBFE] text-[#1E40AF]',
    live: 'pill-live bg-[#D1FAE5] border-[#A7F3D0] text-[#065F46]'
  }
  return map[props.variant] || map.region
})
</script>
