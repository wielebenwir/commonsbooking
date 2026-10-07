<script setup>
import { computed } from 'vue'
import { useRoute } from 'vitepress'

const route = useRoute()

const isDE = computed(() => !route.path.startsWith('/en/'))

const message = computed(() => {
  if (isDE.value) {
    return {
      label: 'ACHTUNG:',
      text: 'Mit CommonsBooking 2.12 (Veröffentlichung Anfang 2027) wird die Migration von CommonsBooking 0.9.X auf die neuste Version eingestellt. Alle Nutzenden von CommonsBooking 0.9.X sind dazu angehalten ',
      bold: 'zeitnah zu migrieren',
      suffix: ', bei Fragen unterstützen wir gerne und helfen euch auch gerne bei der Migration.',
    }
  }
  return {
    label: 'ATTENTION:',
    text: 'With CommonsBooking 2.12 (releasing early 2027), migration support from CommonsBooking 0.9.X to the latest version will end. All users of CommonsBooking 0.9.X are urged to ',
    bold: 'migrate as soon as possible',
    suffix: '. We are happy to help with any questions and will support you when you decide to migrate.',
  }
})
</script>

<template>
  <div class="announcement-banner">
    <div class="announcement-banner__inner">
      <span class="announcement-banner__icon" aria-hidden="true">⚠️</span>
      <div class="announcement-banner__content">
        <strong>{{ message.label }}</strong>
        {{ message.text }}<strong>{{ message.bold }}</strong>{{ message.suffix }}
      </div>
    </div>
  </div>
</template>

<style scoped>
.announcement-banner {
  background-color: var(--vp-custom-block-danger-bg, #fff0f0);
  border-bottom: 2px solid var(--vp-custom-block-danger-border, #e53e3e);
  color: var(--vp-custom-block-danger-text, #7b1111);
  width: 100%;
  z-index: 30;
}

.announcement-banner__inner {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
  max-width: 1152px;
  margin: 0 auto;
  padding: 0.75rem 1.5rem;
  font-size: 0.95rem;
  line-height: 1.6;
}

.announcement-banner__icon {
  flex-shrink: 0;
  font-size: 1.1rem;
  margin-top: 0.1rem;
}

.announcement-banner__content {
  flex: 1;
}

@media (max-width: 640px) {
  .announcement-banner__inner {
    padding: 0.65rem 1rem;
    font-size: 0.9rem;
  }
}
</style>
