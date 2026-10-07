---
layout: home
---

<script setup>
// Gentle locale redirect that respects a stored preference and keeps caches valid
if (typeof window !== 'undefined') {
  const STORAGE_KEY = 'cb-doc-locale';
  const DEFAULT_LOCALE = '/de/';
  const knownLocales = ['/de/', '/en/'];
  const currentPath = window.location.pathname;

  // If we're on a locale path, remember it for future visits
  const matchedLocale = knownLocales.find((prefix) => currentPath.startsWith(prefix));
  if (matchedLocale) {
    localStorage.setItem(STORAGE_KEY, matchedLocale);
  }

  // Only redirect when we're exactly at the root and no locale is in the URL
  const isRoot = matchedLocale === undefined && (currentPath === '/' || currentPath === '/index.html');
  if (isRoot) {
    const stored = localStorage.getItem(STORAGE_KEY);
    const target = knownLocales.includes(stored ?? '') ? stored : DEFAULT_LOCALE;
    window.location.replace(target);
  }
}
</script>

