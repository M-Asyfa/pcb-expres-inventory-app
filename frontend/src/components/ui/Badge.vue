<template>
  <div :class="classes"><slot /></div>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  variant: { type: String, default: 'default' } // default, secondary, success, warning, destructive, outline
})

const classes = computed(()=>{
  const base = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold tracking-wide transition-colors'
  const map = {
    default: 'bg-[#0F1E35] text-white',
    secondary: 'bg-[#F3EBD9] text-[#0F1E35] border border-[#E8DDC7]',
    success: 'bg-[#D1FAE5] text-[#065F46] border border-[#A7F3D0]',
    warning: 'bg-[#FEF3C7] text-[#92400E] border border-[#FDE68A]',
    destructive: 'bg-[#FEE2E2] text-[#B91C1C] border border-[#FECACA]',
    outline: 'bg-white border border-[#E8DDC7] text-[#0F1E35]',
    blue: 'bg-[#DBEAFE] text-[#1E40AF] border border-[#BFDBFE]'
  }
  return [base, map[props.variant] || map.default].join(' ')
})
</script>
