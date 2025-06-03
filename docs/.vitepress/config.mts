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
    ['link', { 'rel': 'icon', type: 'image/png', href: 'img/logo.png' }]
  ],

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config

    logo: { src: 'img/logo.png', width: 24, height: 24 },

    nav: [
    { text: 'Merkmale & Funktionen', link: '/funktionen/' }
  , { text: 'Dokumentation', link: '/dokumentation/', activeMatch: '/dokumentation/' }
  , { text: 'Unterstützende', link: '/supported-by/' }
  , { text: 'Support und Kontakt', link: '/kontakt/' }
  , { text: 'Jetzt Spenden!', link: '/spenden/' }
    , {
      text: '2.9.4',
      items: [
        {
          text: 'Changelog',
          link: 'https://wordpress.org/plugins/commonsbooking/#developers'
        },
        {
          text: 'Contributing',
          link: 'https://github.com/wielebenwir/commonsbooking/blob/master/.github/contributing.md'
        }
      ]
    }
    ],

    sidebar: {
      '/dokumentation/': { base: '/dokumentation/', items: sidebarDocs() },
    },

    editLink: {
      pattern: 'https://github.com/wielebenwir/commonsbooking/edit/master/dokumentation/:path',
      text: 'Bearbeite die Seite auf Github'
    },

    footer: {
      message: 'Released under the GNU v2 License.',
      copyright: 'Copyright © 2019-present wielebenwir e.V.',
    },

    search: {
        provider: 'local'
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/wielebenwir/commonsbooking' },
        { icon: 'wordpress', link: 'https://wordpress.org/plugins/commonsbooking' }
    ]
  }
})

export function sidebarDocs(): DefaultTheme.SidebarItem[] {
  return [
      {
          text: 'Installation', base: '/dokumentation/installation/',
          collapsed: true,
          items: [
              { text: 'Installieren', link: 'installieren' },
              { text: 'Migration von CB1', link: 'migration-von-cb1' },
              { text: 'Theme', link: 'theme' },
              { text: 'Update-Info', link: 'update-info' }
          ]
      },
      {
          text: 'Erste Schritte', base: '/dokumentation/erste-schritte/',
          collapsed: true,
          items: [
              { text: 'Artikel anlegen', link: 'artikel-anlegen' },
              { text: 'Stationen anlegen', link: 'stationen-anlegen' },
              { text: 'Buchungsregeln einrichten', link: 'buchungsregeln-einrichten' },
              { text: 'Buchungseinschränkungen verwalten', link: 'buchungseinschraenkungen-verwalten' },
              { text: 'Zeitrahmen & Feiertage definieren', link: 'zeitrahmen-feiertage-definieren' }
          ]
      },
      {
          text: 'Einstellungen', base: '/dokumentation/einstellungen-2/',
          collapsed: true,
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
      text: 'Grundlagen', base: '/dokumentation/grundlagen/',
      collapsed: true,
      items: [
        { text: 'Begriffe', link: 'begriffe' },
        { text: 'Buchungs-Codes', link: 'buchungs-codes' },
        { text: 'Rechte des CommonsBooking Managers', link: 'rechte-des-commonsbooking-manager' },
        { text: 'Zeitrahmen konfigurieren', link: 'zeitrahmen-konfigurieren' }
      ]
    },


    {
      text: 'Buchungen verwalten', base: '/dokumentation/buchungen-verwalten/',
        collapsed: true,
      items: [
        { text: 'Buchungen anlegen', link: 'buchungen-anlegen' },
        { text: 'Buchung stornieren', link: 'buchung-stornieren' },
        { text: 'iCalendar Feed', link: 'icalendar-feed' }
      ]
    },
      {
          text: 'Administration', base: '/dokumentation/einstellungen/',
          collapsed: true,
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
          text: 'Schnittstellen / API', base: '/dokumentation/schnittstellen-api/',
          collapsed: true,
          items: [
              { text: 'CommonsBooking API', link: 'commonsbooking-api' },
              { text: 'Was ist die CommonsAPI?', link: 'was-ist-die-commonsapi' }
          ]
      },

    {
      text: 'Erweiterte Funktionalität', base: '/dokumentation/erweiterte-funktionalitaet/',
        collapsed: true,
      items: [
        { text: 'Standardwerte für Zeitrahmen-Erstellung ändern', link: 'standardwerte-fuer-zeitrahmenerstellung-aendern' }
      ]
    },

    {
      text: 'Roadmap', base: '/dokumentation/roadmap/',
        collapsed: true,
      items: [
        { text: 'Übersicht über die Releases', link: 'uebersicht-ueber-die-releases' }
      ]
    },
    {
      text: 'Informationen zur alten Version 0.9', base: '/dokumentation/informationen-zur-alten-version-0-9/',
        collapsed: true,
      items: [
        { text: 'Bookings Template Tags v0.9', link: 'bookings-template-tags-verson-0-9' },
        { text: 'Einstellungen v0.9', link: 'einstellungen-version-0-9' },
        { text: 'Erste Schritte v0.9', link: 'erste-schritte' },
        { text: 'FAQ v0.9', link: 'haeufige-fragen-version-0-9' },
        { text: 'Installation', link: 'installation' },
        { text: 'Widgets & Themes v0.9', link: 'widgets-themes-version-0-9' }
      ]
    },
      {
          text: 'Häufige Fragen (FAQ)', base: '/dokumentation/haeufige-fragen-faq/',
          collapsed: true,
          items: [
              { text: 'Die Seite ist sehr langsam', link: 'die-seite-ist-sehr-langsam' },
              { text: 'Probleme und Antworten', link: 'probleme-und-antworten' },
              { text: 'Wie bekomme ich den Buchungskommentar ...', link: 'wie-bekomme-ich-den-buchungskommentar-auf-die-webseite-zu-den-buchungsinformationen-etc-sowohl-als-auch-in-die-email' },
              { text: 'Kann ich Zahlenschloss-Codes in E-Mails einfügen?', link: 'kann-ich-zahlenschloss-codes-in-e-mails-einfuegen' },
              { text: 'Artikeldetailseite übersichtlicher gestalten', link: 'wie-kann-ich-die-artikeldetailseite-uebersichtlicher-gestalten' },
              { text: 'Wie verhindere ich Spam Registrierungen', link: 'wie-verhindere-ich-spam-registrierungen'}
          ]
      },
  ]
}



