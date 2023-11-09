import { createRequire } from 'module'
import { defineConfig, type DefaultTheme } from 'vitepress'

const require = createRequire(import.meta.url)
const pkg = require('../../package.json')

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'de-DE',
  title: "CommonsBooking",
  description: "Benutzerhandbuch und Dokumentation",

  lastUpdated: true,
  cleanUrls: true,

  sitemap: {
    hostname: 'https://commonsbooking.org',
  },

  head: [
    ['link', { 'rel': 'icon', type: 'image/png', href: '/logo.png' }]
  ],

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config

    logo: { src: '/logo.png', width: 24, height: 24 },

    nav: [
     { text: 'Anleitung', link: '/anleitung/', activeMatch: '/anleitung/' }
    , { text: 'Dokumentation', link: '/dokumentation/', activeMatch: '/dokumentation/' }
    , { 
      text: pkg.version,
      items: [
        {
          text: 'Changelog',
          link: 'https://github.com/vuejs/vitepress/blob/main/CHANGELOG.md'
        },
        {
          text: 'Contributing',
          link: 'https://github.com/vuejs/vitepress/blob/main/.github/contributing.md'
        }
      ]
    }
    ],

    sidebar: {
      '/anleitung/':     { base: '/anleitung/',     items: sidebarAnleitung() },
      '/dokumentation/': { base: '/dokumentation/', items: sidebarDokumentation() }
    },

    editLink: {
      pattern: 'https://github.com/wielebenwir/commonsbooking/edit/master/docs/:path',
      text: 'Bearbeite die Seite auf Github'
    },

    footer: {
      message: 'Released under the GNU v2 License.',
      copyright: 'Copyright © 2019-present Wie Leben Wir e.V.'
    }, 
    
    search: {
        provider: 'local'
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/vuejs/vitepress' }
    ]
  }
})

function sidebarAnleitung(): DefaultTheme.SidebarItem[] {
    return [
      {
        text: 'Erste Schritte',
        items: [
          { text: 'Installation',  link: 'installation' }
        , { text: 'Einrichtung',   link: 'einrichtung-1' }
        , { text: 'Erste Buchung', link: 'einrichtung-2' }
        ]
      },
      {
        text: 'Erweitert',
        items: [
          { text: 'Buchungscodes erstellen',  link: 'installation' }
        , { text: 'E-Mail Template anpassen',   link: 'einrichtung-1' }
        , { text: 'Karten einbinden', link: 'einrichtung-2' }
        ]
      },
      {
        text: 'Anwendungsfälle',
        items: [
          { text: 'Andbindung Schloßsystem',  link: 'installation' }
        , { text: 'Einrichtung',   link: 'einrichtung-1' }
        , { text: 'Erste Buchung', link: 'einrichtung-2' }
        ]
      }

    ]
}

function sidebarDokumentation(): DefaultTheme.SidebarItem[] { 
    return [
      {
        text: 'Benutzer-Dokumentation',
        items: [
          { text: 'Artikel',  link: 'artikel' }
        , { text: 'Standorte',   link: 'standorte' }
        , { text: 'Zeitrahmen', link: 'zeitrahmen' }
        , { text: 'Einschränkungen', link: 'einschränkungen' }
        , { text: 'Karten', link: 'karten' }
        , { text: 'Erweitert', link: 'erweitert',
            items: [
              { text: 'Shortcodes', link: 'shortcodes' }
            , { text: 'Hooks und Filter', link: 'hooks-und-filter' }
            ]
          }
        ]
      },
      {
        text: 'Entwickler-Dokumentation',
        items: [
          { text: 'Überblick', link: 'überblick' }
        , { text: 'API-Design', link: 'api-design' }
        , { text: 'Roadmap', 'link': 'roadmap' }
        ]
      }
]
}


