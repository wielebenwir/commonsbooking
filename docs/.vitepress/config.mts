import { createRequire } from 'module'
import { defineConfig, type DefaultTheme } from 'vitepress'

const require = createRequire(import.meta.url)
const pkg = require('../../package.json')

const COMMONSBOOKING_VERSION_STRING = '2.10.5';

// https://vitepress.dev/reference/site-config
export default defineConfig({
  locales: {
    root: {
      label: 'Deutsch',
      lang: 'de-DE',
      description: "Benutzerhandbuch und Dokumentation",
      themeConfig: {
        returnToTopLabel: 'An den Anfang',
        nav: [
          { text: 'Merkmale & Funktionen', link: '/funktionen/' }
        , { text: 'Dokumentation', link: '/dokumentation/', activeMatch: '/dokumentation/' }
        , { text: 'Unterstützende', link: '/supported-by/' }
        , { text: 'Support und Kontakt', link: '/kontakt/' }
        , { text: 'Jetzt Spenden!', link: '/spenden/' }
        , {
          text: COMMONSBOOKING_VERSION_STRING,
          items: [
            {
              text: 'Changelog',
              link: 'https://wordpress.org/plugins/commonsbooking/#developers'
            },
            {
              text: 'Mitmachen',
              link: 'https://github.com/wielebenwir/commonsbooking/blob/master/.github/contributing.md'
            }
          ]
        }
        ],
        lastUpdated: {
            text: 'Zuletzt aktualisiert',
        },
        outlineTitle: 'Auf dieser Seite',
        docFooter: {
            prev: 'Vorherige Seite',
            next: 'Nächste Seite'
        },

        editLink: {
          pattern: 'https://github.com/wielebenwir/commonsbooking/edit/master/docs/:path',
          text: 'Bearbeite diese Seite auf Github'
        },
        sidebar: {
          '/dokumentation/': { items: sidebarDocs_de() },
        },
        footer: {
          message: 'Lizensiert unter der GNU v2 Lizenz. <br> <a href="/impressum/">Impressum</a> | <a href="/datenschutzerklaerung/">Datenschutzerklärung</a>',
          copyright: 'Copyright © 2019-jetzt wielebenwir e.V.',
        },
      }
    },
    'en': {
      label: 'English',
      lang: 'en',
      link: '/en',
      description: "User manual and documentation",
      themeConfig: {
        nav: [
          { text: 'Functions & Features', link: '/en/features/' },
          { text: 'Documentation', link: '/en/documentation/', activeMatch: '/en/documentation/' },
          { text: 'Our Supporters', link: '/en/supported-by/' },
          { text: 'Support & Contact', link: '/en/contact/' },
          { text: 'Donate Now!', link: '/en/donate/' },
          {
            text: COMMONSBOOKING_VERSION_STRING,
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
          editLink: {
            pattern: 'https://github.com/wielebenwir/commonsbooking/edit/master/docs/:path',
            text: 'Edit this page on Github'
          },
          sidebar: {
            '/en/documentation/': {  items: sidebarDocs_en() },
          },
          footer: {
            message: 'Licensed under the GNU v2 License. <br> <a href="/en/imprint/">Imprint</a> | <a href="/en/privacy-policy/">Privacy Policy</a>',
            copyright: 'Copyright © 2019-present wielebenwir e.V.',
          }
        }
    }
  },
  title: "CommonsBooking",
  lastUpdated: true,
  cleanUrls: true,

  sitemap: {
    hostname: 'https://commonsbooking.org',
  },

  head: [
    ['link', { 'rel': 'icon', type: 'image/png', href: '/img/logo.png' }]
  ],

  themeConfig: {
    logo: { src: '/img/logo.png', width: 24, height: 24 },

    search: {
        provider: 'local',
        options: {
          locales: {
            root: {
              translations: {
                button: {
                  buttonText: 'Durchsuchen',
                  buttonAriaLabel: 'Suche',
                },
                modal: {
                  displayDetails: 'Details anzeigen',
                  resetButtonTitle: 'Zurücksetzen',
                  backButtonTitle: 'Zurück',
                  noResultsText: 'Keine Ergebnisse gefunden',
                  footer: {
                    selectText: 'Auswählen',
                    selectKeyAriaLabel: 'Taste zum Auswählen',
                    navigateText: 'Hierhin navigieren',
                    navigateUpKeyAriaLabel: 'Taste zum Navigieren nach oben',
                    navigateDownKeyAriaLabel: 'Taste zum Navigieren nach unten',
                    closeText: 'Schließen',
                    closeKeyAriaLabel: 'Taste zum Schließen',
                }
              }
              }
            }
          }
        }
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/wielebenwir/commonsbooking' },
      { icon: 'wordpress', link: 'https://wordpress.org/plugins/commonsbooking' }
    ]
  }
})

export function sidebarDocs_de(): DefaultTheme.SidebarItem[] {
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
              { text: 'Erste Schritte', link: 'index' },
              { text: 'Artikel anlegen', link: 'artikel-anlegen' },
              { text: 'Stationen anlegen', link: 'stationen-anlegen' },
              { text: 'Buchungszeiträume verwalten', link: 'buchungszeitraeume-verwalten'},
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
              { text: 'Leihangebote im Frontend anzeigen', link: 'leihangebote-im-frontend-anzeigen' },
              { text: 'Buchungsliste', link: 'buchungsliste' },
              { text: 'Karte einbinden', link: 'karte-einbinden' },
              { text: 'Neue Karte', link: 'neues-frontend-beta' },
              { text: 'Shortcodes', link: 'shortcodes' },
              { text: 'Template Tags', link: 'template-tags' },
              { text: 'Widget', link: 'widget' },
              { text: 'Registrierungsseiten & Benutzerfelder anpassen', link: 'registrierungs-seiten-und-benutzerfelder-anpassen' },
              { text: 'Hooks und Filter', link: 'hooks-und-filter' },
          ]
      },
      {
          text: 'Schnittstellen / API', base: '/dokumentation/schnittstellen-api/',
          link: '/',
          collapsed: true,
          items: [
              { text: 'Was ist die CommonsAPI?', link: 'was-ist-die-commonsapi' },
              { text: 'CommonsBooking API', link: 'commonsbooking-api' },
              { text: 'GBFS', link: 'gbfs'}
          ]
      },

    {
      text: 'Erweiterte Funktionalität', base: '/dokumentation/erweiterte-funktionalitaet/',
        collapsed: true,
      items: [
          { text: 'Caching', link: 'cache' },
          { text: 'Standardwerte für Zeitrahmen-Erstellung ändern', link: 'standardwerte-fuer-zeitrahmenerstellung-aendern' },
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
        { text: 'Installation', link: 'installation' },
        { text: 'Erste Schritte v0.9', link: 'erste-schritte' },
        { text: 'Bookings Template Tags v0.9', link: 'bookings-template-tags-version-0-9' },
        { text: 'Einstellungen v0.9', link: 'einstellungen-version-0-9' },
        { text: 'Widgets & Themes v0.9', link: 'widgets-themes-version-0-9' },
        { text: 'FAQ v0.9', link: 'haeufige-fragen-version-0-9' }
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

export function sidebarDocs_en(): DefaultTheme.SidebarItem[] {
  return [
      {
          text: 'Setup', base: '/en/documentation/setup/',
          collapsed: true,
          items: [
              { text: 'Install', link: 'install' },
              //{ text: 'Migration from CB1', link: 'migration-from-cb1' },
              //{ text: 'Theme', link: 'theme' },
              //{ text: 'Update-News', link: 'update-news' }
          ]
      },
      {
          text: 'First steps', base: '/en/documentation/first-steps/',
          collapsed: true,
          items: [
              { text: 'First steps', link: 'index' },
              { text: 'Create item', link: 'create-item' },
              { text: 'Create location', link: 'create-location' },
              { text: 'Manage booking timeframes', link: 'booking-timeframes-manage'},
              { text: 'Set up booking rules', link: 'setup-bookingrules' },
              { text: 'Manage booking restrictions', link: 'manage-booking-restrictions' },
              { text: 'Configure a location\'s holidays', link: 'timeframes-holidays' }
          ]
      },
      /*
      {
          text: 'Settings', base: '/en/documentation/settings/',
          collapsed: true,
          items: [
              { text: 'General Settings', link: 'general-settings' },
              { text: 'Booking codes', link: 'booking-codes' },
              { text: 'Restrictions', link: 'restrictions' },
              { text: 'Templates', link: 'templates' },
              { text: 'Reminder', link: 'reminder' },
              { text: 'Export', link: 'export' },
              { text: 'Language settings & date format', link: 'language-and-date' }
          ]
      },
    {
      text: 'Basics', base: '/en/documentation/basics/',
      collapsed: true,
      items: [
        { text: 'Concepts', link: 'concepts' },
        { text: 'Booking codes', link: 'booking-codes' },
        { text: 'Permission management (CB-Manager)', link: 'permission-management' },
        { text: 'Configure timeframes', link: 'timeframes-config' }
      ]
    },


    {
      text: 'Manage bookings', base: '/en/documentation/manage-bookings/',
        collapsed: true,
      items: [
        { text: 'Create bookings', link: 'bookings-create' },
        { text: 'Cancel bookings', link: 'bookings-cancel' },
        { text: 'iCalendar Feed', link: 'icalendar-feed' }
      ]
    },
      {
          text: 'Administration', base: '/en/documentation/administration/',
          collapsed: true,
          items: [
              { text: 'Booking list', link: 'booking-list' },
              { text: 'Hooks and Filter', link: 'hooks-and-filters' },
              { text: 'Embed map', link: 'map-embed' },
              { text: 'Show bookable items in the frontend', link: 'frontend-show-bookable' },
              { text: 'New Frontend(Beta)', link: 'new-frontend' },
              { text: 'Adjust registration page and user fields', link: 'custom-registration-user-fields' },
              { text: 'Shortcodes', link: 'shortcodes' },
              { text: 'Template Tags', link: 'template-tags' },
              { text: 'Widget', link: 'widget' }
          ]
      },
      {
          text: 'Extensions / API', base: '/en/documentation/api/',
          collapsed: true,
          items: [
              { text: 'What is the CommonsAPI?', link: 'what-is-the-commonsapi' },
              { text: 'CommonsBooking API', link: 'commonsbooking-api' }
          ]
      },
*/
    {
      text: 'Advanced functionality', base: '/en/documentation/advanced-functionality/',
        collapsed: true,
      items: [
            { text: 'Caching', link: 'cache' },
        //{ text: 'Change default values for timeframe creation', link: 'change-timeframe-creation-defaults' }
      ]
    },
/*
    {
      text: 'Roadmap', base: '/en/documentation/roadmap/',
        collapsed: true,
      items: [
        { text: 'Release overview', link: 'release-overview' }
      ]
    },
    */
    /* Won't be added to English version
    {
      text: 'Informationen zur alten Version 0.9', base: '/dokumentation/informationen-zur-alten-version-0-9/',
        collapsed: true,
      items: [
        { text: 'Bookings Template Tags v0.9', link: 'bookings-template-tags-version-0-9' },
        { text: 'Einstellungen v0.9', link: 'einstellungen-version-0-9' },
        { text: 'Erste Schritte v0.9', link: 'erste-schritte' },
        { text: 'FAQ v0.9', link: 'haeufige-fragen-version-0-9' },
        { text: 'Installation', link: 'installation' },
        { text: 'Widgets & Themes v0.9', link: 'widgets-themes-version-0-9' }
      ]
    },
    */
   /*
      {
          text: 'Frequently Asked Questions (FAQ)', base: '/en/documentation/faq/',
          collapsed: true,
          items: [
              { text: 'My site is very slow', link: 'site-slow' },
              { text: 'Problems and answers', link: 'problems-and-answers' },
              { text: 'Add booking comment to email templates', link: 'booking-comment-emails' },
              { text: 'How to add lock codes in email templates', link: 'lock-codes-email' },
              { text: 'Make article page look more organised', link: 'organise-article-page' },
              { text: 'How to avoid spam registrations', link: 'avoid-spam-registrations'}
          ]
      },
      */
  ]
}


