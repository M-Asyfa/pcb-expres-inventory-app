<template>
  <div :class="['bg-[var(--color-card)] rounded-[var(--radius-card)] border border-[var(--color-border)] shadow-[var(--shadow-card)]', $attrs.class]">
    <slot />
  </div>
</template>
