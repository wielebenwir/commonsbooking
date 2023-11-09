import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "CommonsBooking",
  description: "Benutzerhandbuch und Dokumentation",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' }
    , { text: 'Anleitung', link: '/anleitung' }
    , { text: 'Dokumentation', link: '/dokumentation' }
    ],
    
    search: {
        provider: 'local'
    },

    sidebar: [
      {
        text: 'Anleitung',
        link: '/anleitung',
        items: [
          { text: 'Installation',  link: '/installation' }
        , { text: 'Einrichtung',   link: '/einrichtung-1' }
        , { text: 'Erste Buchung', link: '/einrichtung-2' }
        ]
      },
      {
        text: 'Dokumentation',
        link: '/dokumentation',
        items: [
          { text: 'Artikel',  link: '/installation' }
        , { text: 'Standorte',   link: '/einrichtung-1' }
        , { text: 'Zeitrahmen', link: '/einrichtung-2' }
        , { text: 'Einschränkungen', link: '' }
        , { text: 'Karten', link: '' }
        , { text: 'Hooks und Filter', link: '/hooks-und-filter' }
        ]
      }

    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/vuejs/vitepress' }
    ]
  }
})
