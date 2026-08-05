<template>
  <button :class="classes" :disabled="disabled" @click="$emit('click', $event)">
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({
  variant: { type: String, default: 'default' }, // default, secondary, ghost, destructive, pill
  size: { type: String, default: 'default' }, // sm, default, lg, icon
  disabled: { type: Boolean, default: false }
})

const classes = computed(()=>{
  const base = 'inline-flex items-center justify-center rounded-[12px] font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F1E35]/20 disabled:opacity-50 disabled:pointer-events-none'
  const variants = {
    default: 'bg-[var(--color-primary)] text-[var(--color-primary-foreground)] hover:bg-[var(--color-primary-hover)] shadow-sm',
    secondary: 'bg-white text-[#0F1E35] border border-[var(--color-border)] hover:bg-[#F3EBD9]',
    ghost: 'bg-transparent hover:bg-white/60 text-[#0F1E35]',
    destructive: 'bg-[#DC2626] text-white hover:bg-[#B91C1C]',
    pill: 'bg-white border border-[#E8DDC7] rounded-full text-[11px] font-semibold tracking-wide px-3 py-1.5'
  }
  const sizes = {
    sm: 'h-8 px-3 text-xs',
    default: 'h-10 px-4 py-2 text-sm',
    lg: 'h-11 px-8 text-sm',
    icon: 'h-10 w-10'
  }
  return [base, variants[props.variant] || variants.default, sizes[props.size] || sizes.default].join(' ')
})
</script>
