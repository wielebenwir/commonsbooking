// .vitepress/theme/index.js
import { h, onMounted } from 'vue'
import DefaultTheme from 'vitepress/theme'
import './custom.css'
import mediumZoom from 'medium-zoom'
import { useData } from 'vitepress'
import AnnouncementBanner from '../components/AnnouncementBanner.vue'

export default {
    ...DefaultTheme,
    Layout() {
        return h(DefaultTheme.Layout, null, {
            'home-hero-before': () => h(AnnouncementBanner),
        })
    },
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
