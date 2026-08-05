<template>
  <input :type="type" :placeholder="placeholder" :value="modelValue" @input="onInput" class="flex h-10 w-full rounded-[12px] border border-[var(--color-border)] bg-white px-4 py-2 text-sm ring-offset-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F1E35]/20 focus-visible:border-[#0F1E35]/30 disabled:cursor-not-allowed disabled:opacity-50" />
</template>

<script setup>
defineProps({
  modelValue: [String, Number],
  placeholder: String,
  type: { type: String, default: 'text' }
})
const emit = defineEmits(['update:modelValue', 'input'])
const onInput = (e) => {
  emit('update:modelValue', e.target.value)
  emit('input', e)
}
</script>
