import { createRequire } from 'module'
import { defineConfig, type DefaultTheme } from 'vitepress'

const require = createRequire(import.meta.url)
const pkg = require('../../package.json')

const COMMONSBOOKING_VERSION_STRING = '2.10.8';

// https://vitepress.dev/reference/site-config
export default defineConfig({
  locales: {
    root: {
      label: 'Deutsch',
      lang: 'de-DE',
      link: '/de/',
      description: "Benutzerhandbuch und Dokumentation",
      themeConfig: {
        returnToTopLabel: 'An den Anfang',
        nav: [
          { text: 'Merkmale & Funktionen', link: '/de/features/' }
        , { text: 'Dokumentation', link: '/de/documentation/', activeMatch: '/de/documentation/' }
        , { text: 'Unterstützende', link: '/de/supported-by/' }
        , { text: 'Support und Kontakt', link: '/de/contact/' }
        , { text: 'Jetzt Spenden!', link: '/de/donate/' }
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
        darkModeSwitchLabel: 'Themenwechsel',
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
          '/de/documentation/': { items: sidebarDocs_de() },
        },
        footer: {
          message: 'Lizensiert unter der GNU v2 Lizenz. <br> <a href="/de/imprint/">Impressum</a> | <a href="/de/privacy-policy/">Datenschutzerklärung</a>',
          copyright: 'Copyright © 2019-jetzt wielebenwir e.V.',
        },
      }
    },
    'en': {
      label: 'English',
      lang: 'en',
      link: '/en/',
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
      text: 'Installation', base: '/de/documentation/setup/',
          collapsed: true,
          items: [
        { text: 'Installieren', link: 'install' },
        { text: 'Migration von CB1', link: 'migration-from-cb1' },
        { text: 'Theme', link: 'theme' },
        { text: 'Update-Info', link: 'update-info' }
          ]
      },
      {
      text: 'Erste Schritte', base: '/de/documentation/first-steps/',
          collapsed: true,
          items: [
              { text: 'Erste Schritte', link: 'index' },
        { text: 'Artikel anlegen', link: 'create-item' },
        { text: 'Stationen anlegen', link: 'create-location' },
        { text: 'Buchungszeiträume verwalten', link: 'booking-timeframes-manage'},
        { text: 'Buchungsregeln einrichten', link: 'setup-bookingrules' },
        { text: 'Buchungseinschränkungen verwalten', link: 'manage-booking-restrictions' },
        { text: 'Zeitrahmen & Feiertage definieren', link: 'timeframes-holidays' }
          ]
      },
      {
      text: 'Einstellungen', base: '/de/documentation/settings/',
          collapsed: true,
          items: [
        { text: 'Allgemeine Einstellungen', link: 'general-settings' },
        { text: 'Buchungscodes', link: 'booking-codes' },
        { text: 'Einschränkungen', link: 'restrictions' },
        { text: 'E-Mail Vorlagen', link: 'templates' },
        { text: 'Erinnerungs-E-Mail', link: 'reminder' },
              { text: 'Export', link: 'export' },
        { text: 'Spracheinstellung & Datumsformat', link: 'language-and-date' }
          ]
      },
    {
    text: 'Grundlagen', base: '/de/documentation/basics/',
      collapsed: true,
      items: [
    { text: 'Begriffe', link: 'concepts' },
    { text: 'Buchungs-Codes', link: 'booking-codes' },
    { text: 'Rechte des CommonsBooking Managers', link: 'permission-management' },
    { text: 'Zeitrahmen konfigurieren', link: 'timeframes-config' }
      ]
    },


    {
      text: 'Buchungen verwalten', base: '/de/documentation/manage-bookings/',
        collapsed: true,
      items: [
        { text: 'Buchungen anlegen', link: 'bookings-create' },
        { text: 'Buchung stornieren', link: 'bookings-cancel' },
        { text: 'iCalendar Feed', link: 'icalendar-feed' }
      ]
    },
      {
          text: 'Administration', base: '/de/documentation/administration/',
          collapsed: true,
          items: [
              { text: 'Leihangebote im Frontend anzeigen', link: 'frontend-show-bookable' },
              { text: 'Buchungsliste', link: 'booking-list' },
              { text: 'Karte einbinden', link: 'map-embed' },
              { text: 'Neue Karte', link: 'new-frontend' },
              { text: 'Shortcodes', link: 'shortcodes' },
              { text: 'Template Tags', link: 'template-tags' },
              { text: 'Widget', link: 'widget' },
              { text: 'Datenschutz', link: 'privacy'},
              { text: 'Registrierungsseiten & Benutzerfelder anpassen', link: 'custom-registration-user-fields' },
              { text: 'Hooks und Filter', link: 'hooks-and-filters' },
          ]
      },
      {
          text: 'Schnittstellen / API', base: '/de/documentation/api/',
          link: '/',
          collapsed: true,
          items: [
              { text: 'Was ist die CommonsAPI?', link: 'what-is-the-commonsapi' },
              { text: 'CommonsBooking API', link: 'commonsbooking-api' },
              { text: 'GBFS', link: 'gbfs'}
          ]
      },

    {
      text: 'Erweiterte Funktionalität', base: '/de/documentation/advanced-functionality/',
        collapsed: true,
      items: [
          { text: 'Caching', link: 'cache' },
          { text: 'Standardwerte für Zeitrahmen-Erstellung ändern', link: 'change-timeframe-creation-defaults' },
      ]
    },

    {
      text: 'Roadmap', base: '/de/documentation/roadmap/',
        collapsed: true,
      items: [
        { text: 'Übersicht über die Releases', link: 'uebersicht-ueber-die-releases' }
      ]
    },
    {
      text: 'Informationen zur alten Version 0.9', base: '/de/documentation/version-0-9/',
        collapsed: true,
      items: [
        { text: 'Installation', link: 'installation' },
        { text: 'Erste Schritte v0.9', link: 'erste-schritte' },
        { text: 'Bookings Template Tags v0.9', link: 'bookings-template-tags-version-0-9' },
        { text: 'Einstellungen v0.9', link: 'einstellungen-version-0-9' },
        { text: 'Widgets & Themes v0.9', link: 'widgets-themes' },
        { text: 'FAQ v0.9', link: 'haeufige-fragen-version-0-9' }
      ]
    },
      {
          text: 'Häufige Fragen (FAQ)', base: '/de/documentation/faq/',
          link: '/',
          collapsed: true,
          items: [
              { text: 'Die Seite ist sehr langsam', link: 'slow-page' },
              { text: 'Probleme und Antworten', link: 'issues-and-answers' },
              { text: 'Wie bekomme ich den Buchungskommentar ...', link: 'booking-comment-email' },
              { text: 'Kann ich Zahlenschloss-Codes in E-Mails einfügen?', link: 'lock-codes-email' },
              { text: 'Artikeldetailseite übersichtlicher gestalten', link: 'item-detail-page' },
              { text: 'Wie verhindere ich Spam Registrierungen', link: 'prevent-spam-registrations'}
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
    /*
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
    }*/
      {
          text: 'Administration', base: '/en/documentation/administration/',
          collapsed: true,
          items: [
//              { text: 'Booking list', link: 'booking-list' },
                { text: 'Hooks and filters', link: 'hooks-and-filters' },
//              { text: 'Embed map', link: 'map-embed' },
//              { text: 'Show bookable items in the frontend', link: 'frontend-show-bookable' },
//              { text: 'New Frontend(Beta)', link: 'new-frontend' },
//              { text: 'Adjust registration page and user fields', link: 'custom-registration-user-fields' },
//              { text: 'Shortcodes', link: 'shortcodes' },
//              { text: 'Template Tags', link: 'template-tags' },
//              { text: 'Widget', link: 'widget' },
                { text: 'Privacy', link: 'privacy'}
          ]
      },
      /*
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
            { text: 'Change default values for timeframe creation', link: 'change-timeframe-creation-defaults' }
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
      text: 'Informationen zur alten Version 0.9', base: '/en/documentation/version-0-9/',
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


