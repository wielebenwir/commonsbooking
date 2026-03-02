// .vitepress/theme/index.js
import DefaultTheme from 'vitepress/theme'
import './custom.css'
import { onMounted } from 'vue';
import mediumZoom from 'medium-zoom';
import { useData } from 'vitepress';

export default {
  ...DefaultTheme,
  setup() {
    const { page } = useData();

    onMounted(() => {
      const LOCALE_STORAGE_KEY = 'cb-doc-locale';
      const path = window.location.pathname;
      const locale = path.startsWith('/en/') ? '/en/' : path.startsWith('/de/') ? '/de/' : '/de/';

      // Persist the currently viewed locale so root can reuse the choice later
      localStorage.setItem(LOCALE_STORAGE_KEY, locale);

      // Keep zoom working across locales
      mediumZoom('[data-zoomable]', { background: 'var(--vp-c-bg)' });
    });
  },
};
