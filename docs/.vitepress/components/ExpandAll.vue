<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'

const props = defineProps<{
  labelExpand?: string
  labelCollapse?: string
}>()

const allOpen = ref(false)

function getDetails(): HTMLDetailsElement[] {
  return Array.from(document.querySelectorAll<HTMLDetailsElement>('.vp-doc details'))
}

function toggle() {
  const els = getDetails()
  const target = !els.every(d => d.open)
  els.forEach(d => { d.open = target })
  allOpen.value = target
}

onMounted(async () => {
  await nextTick()
  const els = getDetails()
  allOpen.value = els.length > 0 && els.every(d => d.open)
})
</script>

<template>
  <button class="expand-all-btn" type="button" @click="toggle">
    {{ allOpen ? (labelCollapse ?? 'Collapse all') : (labelExpand ?? 'Expand all') }}
  </button>
</template>

<style scoped>
.expand-all-btn {
  display: inline-block;
  padding: 6px 14px;
  margin: 0 0 1.5em;
  font-size: 14px;
  font-weight: 500;
  color: var(--vp-c-brand-1);
  background: transparent;
  border: 1px solid var(--vp-c-brand-1);
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.expand-all-btn:hover {
  background: var(--vp-c-brand-1);
  color: var(--vp-c-bg);
}
</style>
