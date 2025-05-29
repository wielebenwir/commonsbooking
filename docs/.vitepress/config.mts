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
      { text: 'Anleitung',      link: '/anleitung/',       activeMatch: '/anleitung/' }
    , { text: 'Dokumentation',  link: '/dokumentation/',    activeMatch: '/dokumentation/' }
    , { text: 'Doku2', link: '/docs/', activeMatch: '/docs/' }
    , {
      text: pkg.version,
      items: [
        {
          text: 'Changelog',
          link: 'https://github.com/wielebenwir/commonsbooking/blob/master/CHANGELOG.md'
        },
        {
          text: 'Contributing',
          link: 'https://github.com/wielebenwir/commonsbooking/blob/master/.github/contributing.md'
        }
      ]
    }
    ],

    sidebar: {
      '/anleitung/':     { base: '/anleitung/',     items: sidebarAnleitung() },
      '/dokumentation/': { base: '/dokumentation/', items: sidebarDokumentation() },
      '/docs/': { base: '/docs/', items: sidebarDocs() }
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

export function sidebarDocs(): DefaultTheme.SidebarItem[] {
  return [
    {
      text: 'Grundlagen', base: '/docs/grundlagen/',
      items: [
        { text: 'Begriffe', link: 'begriffe' },
        { text: 'Buchungs-Codes', link: 'buchungs-codes' },
        { text: 'Rechte des CommonsBooking Managers', link: 'rechte-des-commonsbooking-manager' },
        { text: 'Zeitrahmen konfigurieren', link: 'zeitrahmen-konfigurieren' }
      ]
    },
    {
      text: 'Erste Schritte', base: '/docs/erste-schritte/',
      items: [
        { text: 'Artikel anlegen', link: 'artikel-anlegen' },
        { text: 'Stationen anlegen', link: 'stationen-anlegen' },
        { text: 'Buchungsregeln einrichten', link: 'buchungsregeln-einrichten' },
        { text: 'Buchungseinschränkungen verwalten', link: 'buchungseinschraenkungen-verwalten' },
        { text: 'Zeitrahmen & Feiertage definieren', link: 'zeitrahmen-feiertage-definieren' }
      ]
    },
    {
      text: 'Buchungen verwalten', base: '/docs/buchungen-verwalten/',
      items: [
        { text: 'Buchungen anlegen', link: 'buchungen-anlegen' },
        { text: 'Buchung stornieren', link: 'buchung-stornieren' },
        { text: 'iCalendar Feed', link: 'icalendar-feed' }
      ]
    },
    {
      text: 'Einstellungen', base: '/docs/einstellungen/',
      items: [
        { text: 'Buchungsliste', link: 'buchungsliste' },
        { text: 'Hooks und Filter', link: 'hooks-und-filter' },
        { text: 'Karte einbinden', link: 'karte-einbinden' },
        { text: 'Leihangebote im Frontend anzeigen', link: 'leihangebote-im-frontend-anzeigen' },
        { text: 'Neues Frontend (Beta)', link: 'neues-frontend-beta' },
        { text: 'Registrierungsseiten & Benutzerfelder anpassen', link: 'registrierungs-seiten-und-benutzerfelder-anpassen' },
        { text: 'Shortcodes', link: 'shortcodes' },
        { text: 'Template Tags', link: 'template-tags' },
        { text: 'Widget', link: 'widget' }
      ]
    },
    {
      text: 'Weitere Einstellungen', base: '/docs/einstellungen-2/',
      items: [
        { text: 'Allgemeine Einstellungen', link: 'allgemeine-einstellungen' },
        { text: 'Buchungscodes', link: 'buchungscodes' },
        { text: 'Einschränkungen', link: 'einschraenkungen' },
        { text: 'E-Mail Vorlagen', link: 'e-mail-vorlagen' },
        { text: 'Erinnerungs-E-Mail', link: 'erinnerungs-e-mail' },
        { text: 'Export', link: 'export' },
        { text: 'Spracheinstellung & Datumsformat', link: 'spracheinstellung-datumsformat' }
      ]
    },
    {
      text: 'Erweiterte Funktionalität', base: '/docs/erweiterte-funktionalitaet/',
      items: [
        { text: 'Standardwerte für Zeitrahmen-Erstellung ändern', link: 'standardwerte-fuer-zeitrahmenerstellung-aendern' }
      ]
    },
    {
      text: 'Schnittstellen / API', base: '/docs/schnittstellen-api/',
      items: [
        { text: 'CommonsBooking API', link: 'commonsbooking-api' },
        { text: 'Was ist die CommonsAPI?', link: 'was-ist-die-commonsapi' }
      ]
    },
    {
      text: 'Häufige Fragen (FAQ)', base: '/docs/haeufige-fragen-faq/',
      items: [
        { text: 'Die Seite ist sehr langsam', link: 'die-seite-ist-sehr-langsam' },
        { text: 'Probleme und Antworten', link: 'probleme-und-antworten' },
        { text: 'Wie bekomme ich den Buchungskommentar ...', link: 'wie-bekomme-ich-den-buchungskommentar-auf-die-webseite-zu-den-buchungsinformationen-etc-sowohl-als-auch-in-die-email' },
        { text: 'Kann ich Zahlenschloss-Codes in E-Mails einfügen?', link: 'kann-ich-zahlenschloss-codes-in-e-mails-einfuegen' },
        { text: 'Artikeldetailseite übersichtlicher gestalten', link: 'wie-kann-ich-die-artikeldetailseite-uebersichtlicher-gestalten' }
      ]
    },
    {
      text: 'Installation', base: '/docs/installation/',
      items: [
        { text: 'Installieren', link: 'installieren' },
        { text: 'Migration von CB1', link: 'migration-von-cb1' },
        { text: 'Theme', link: 'theme' },
        { text: 'Update-Info', link: 'update-info' }
      ]
    },
    {
      text: 'Roadmap', base: '/docs/roadmap/',
      items: [
        { text: 'Übersicht über die Releases', link: 'uebersicht-ueber-die-releases' }
      ]
    },
    {
      text: 'Informationen zur alten Version 0.9', base: '/docs/informationen-zur-alten-version-0-9/',
      items: [
        { text: 'Bookings Template Tags v0.9', link: 'bookings-template-tags-verson-0-9' },
        { text: 'Einstellungen v0.9', link: 'einstellungen-version-0-9' },
        { text: 'Erste Schritte v0.9', link: 'erste-schritte' },
        { text: 'FAQ v0.9', link: 'haeufige-fragen-version-0-9' },
        { text: 'Installation', link: 'installation' },
        { text: 'Widgets & Themes v0.9', link: 'widgets-themes-version-0-9' }
      ]
    }
  ]
}


function sidebarAnleitung(): DefaultTheme.SidebarItem[] {
    return [
      {
        text: 'Grundlagen', base: '/anleitung/',
        items: [
          { text: 'Installation',  link: 'installation' }
        , { text: 'Einrichtung',   link: 'einrichtung-1' }
        , { text: 'Erste Buchung', link: 'einrichtung-2' }
        ]
      },
      {
        text: 'Erweitert', base: '/anleitung/use-cases/',
        items: [
          { text: 'Buchungscodes erstellen',  link: 'buchungscodes-erstellen' }
        , { text: 'E-Mail Template anpassen',   link: 'email-template-anpassen' }
        , { text: 'Karten einbinden', link: 'karten-anbinden' }
        ]
      },
      {
        text: 'Anwendungsfälle', base: '/anleitung/use-cases/',
        items: [
          { text: 'Anbindung Schloßsystem',  link: 'externes-schloss-system' }
        , { text: 'Einrichtung',   link: 'einrichtung-1' }
        , { text: 'Erste Buchung', link: 'einrichtung-2' }
        ]
      }

    ]
}

function sidebarDokumentation(): DefaultTheme.SidebarItem[] {
    return [
      {
        text: 'Benutzer-Dokumentation', link: 'user',
        items: [
          { text: 'Artikel',  link: 'artikel' }
        , { text: 'Standorte',   link: 'standorte' }
        , { text: 'Zeitrahmen', link: 'zeitrahmen' }
        , { text: 'Einschränkungen', link: 'einschränkungen' }
        , { text: 'Karte', link: 'karte' }
        , { text: 'Erweitert', link: 'erweitert',
            items: [
              { text: 'Shortcodes', link: 'shortcodes' }
            , { text: 'Hooks und Filter', link: 'hooks-und-filter' }
            ]
          }
        , { text: 'Administration',
            items: [
              { text: 'Rollen', link: 'rollen' }
            , { text: 'Plugins', link: 'plugins' }
            , { text: 'Buchungs-Codes', link: 'buchungs-codes' }
            ]
          }
        ]
      },
      {
        text: 'Entwickler-Dokumentation', link: 'dev',
        items: [
          { text: 'Überblick', link: 'dev-überblick' }
        , { text: 'API-Design', link: 'api-design' }
        , { text: 'Roadmap', 'link': 'roadmap' }
        ]
      }
]
}


