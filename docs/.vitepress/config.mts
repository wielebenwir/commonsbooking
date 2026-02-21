import { createRequire } from 'module'
import { defineConfig, type DefaultTheme } from 'vitepress'

const require = createRequire(import.meta.url)
const pkg = require('../../package.json')

const COMMONSBOOKING_VERSION_STRING = '2.10.9';

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
        { text: 'Update-Info', link: 'update-news' }
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
              { text: 'Shortcodes für Frontend-Darstellung', link: 'shortcodes' },
              { text: 'Template Tags', link: 'template-tags' },
              { text: 'Widget', link: 'widget' },
              { text: 'Datenschutz', link: 'privacy'},
              { text: 'Registrierungsseiten & Benutzerfelder anpassen', link: 'custom-registration-user-fields' },
          ]
      },
      {
          text: 'Schnittstellen / API', base: '/de/documentation/api/',
          link: '/',
          collapsed: true,
          items: [
              { text: 'Was ist die CommonsAPI?', link: 'what-is-the-commonsapi' },
              { text: 'CommonsBooking API nutzen', link: 'commonsbooking-api' },
              { text: 'GBFS', link: 'gbfs'}
          ]
      },

    {
      text: 'Erweiterte Funktionalität', base: '/de/documentation/advanced-functionality/',
        collapsed: true,
      items: [
          { text: 'Cache einstellen', link: 'cache' },
          { text: 'Standardwerte für Zeitrahmenerstellung ändern', link: 'change-timeframe-creation-defaults' },
          { text: 'Hooks und Filter', link: 'hooks-and-filters' },
      ]
    },

    {
      text: 'Roadmap', base: '/de/documentation/roadmap/',
        collapsed: true,
      items: [
        { text: 'Übersicht über die Releases', link: 'release-overview' }
      ]
    },
    {
      text: 'Informationen zur alten Version 0.9', base: '/de/documentation/version-0-9/',
        collapsed: true,
      items: [
        { text: 'Installation (Version 0.9.x)', link: 'setup' },
        { text: 'Erste Schritte (Version 0.9.x)', link: 'first-steps' },
        { text: 'Frontend-Einbindung (Version 0.9.x)', link: 'bookings-template-tags' },
        { text: 'Login- und Registrierungsseiten anpassen (Version (0.9.x)', link: 'settings' },
        { text: 'Widgets & Themes (Version 0.9.x)', link: 'widgets-themes' },
        { text: 'Häufige Fragen (Version 0.9.x)', link: 'faq' }
      ]
    },
      {
          text: 'Häufige Fragen (FAQ)', base: '/de/documentation/faq/',
          link: '/',
          collapsed: true,
          items: [
              { text: 'Die Seite ist sehr langsam', link: 'site-slow' },
              { text: 'Probleme und Antworten', link: 'problems-and-answers' },
              { text: 'Wie bekomme ich den Buchungskommentar ...', link: 'booking-comment-emails' },
              { text: 'Kann ich Zahlenschloss-Codes in E-Mails einfügen?', link: 'lock-codes-email' },
              { text: 'Artikeldetailseite übersichtlicher gestalten', link: 'organise-article-page' },
              { text: 'Wie verhindere ich Spam Registrierungen', link: 'avoid-spam-registrations'}
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
              { text: 'Migration from version 0.9.x', link: 'migration-from-cb1' },
              { text: 'Choose a WordPress theme', link: 'theme' },
              { text: 'Update information', link: 'update-news' }
          ]
      },
      {
          text: 'First steps', base: '/en/documentation/first-steps/',
          collapsed: true,
          items: [
              { text: 'First steps', link: 'index' },
              { text: 'Create item', link: 'create-item' },
              { text: 'Create location', link: 'create-location' },
              { text: 'Timeframes: Define when an item can be booked', link: 'booking-timeframes-manage'},
              { text: 'Configure booking rules (since 2.9)', link: 'setup-bookingrules' },
              { text: 'Manage restrictions', link: 'manage-booking-restrictions' },
              { text: 'Timeframes: Configure a location\'s holidays', link: 'timeframes-holidays' }
          ]
      },
      {
          text: 'Settings', base: '/en/documentation/settings/',
          collapsed: true,
          items: [
              { text: 'General settings', link: 'general-settings' },
              { text: 'Booking codes', link: 'booking-codes' },
              { text: 'Restrictions', link: 'restrictions' },
              { text: 'Templates', link: 'templates' },
              { text: 'Reminder via email concerning bookings', link: 'reminder' },
              { text: 'Export timeframes and bookings', link: 'export' },
              { text: 'Language & date format', link: 'language-and-date' }
          ]
      },
    {
      text: 'Basics', base: '/en/documentation/basics/',
      collapsed: true,
      items: [
        { text: 'Concepts', link: 'concepts' },
        { text: 'Booking codes', link: 'booking-codes' },
        { text: 'Assign access rights (CB Manager)', link: 'permission-management' },
        { text: 'Timeframe types', link: 'timeframes-config' }
      ]
    },


    {
      text: 'Manage bookings', base: '/en/documentation/manage-bookings/',
        collapsed: true,
      items: [
        { text: 'Create Bookings & Admin Booking', link: 'bookings-create' },
        { text: 'Cancel Bookings', link: 'bookings-cancel' },
        { text: 'iCalendar Feed', link: 'icalendar-feed' }
      ]
    },
      {
          text: 'Administration', base: '/en/documentation/administration/',
          collapsed: true,
          items: [
                { text: 'Displaying lending offers in the frontend', link: 'frontend-show-bookable' },
                { text: 'Booking list', link: 'booking-list' },
                { text: 'Location map with filters', link: 'map-embed' },
                { text: 'New frontend (BETA)', link: 'new-frontend' },
                { text: 'Shortcodes for frontend display', link: 'shortcodes' },
                { text: 'Template tags and placeholders for email templates', link: 'template-tags' },
                { text: 'Integrating the user widget', link: 'widget' },
                { text: 'Privacy', link: 'privacy' },
                { text: 'Customizing registration & login', link: 'custom-registration-user-fields' }
          ]
      },
      {
          text: 'Extensions / API', base: '/en/documentation/api/',
          link: '/',
          collapsed: true,
          items: [
              { text: 'What is the CommonsAPI?', link: 'what-is-the-commonsapi' },
              { text: 'Using the CommonsBooking API', link: 'commonsbooking-api' },
              { text: 'GBFS', link: 'gbfs' }
          ]
      },
    {
      text: 'Advanced functionality', base: '/en/documentation/advanced-functionality/',
        collapsed: true,
      items: [
            { text: 'Configure cache', link: 'cache' },
            { text: 'Change default values for timeframe creation', link: 'change-timeframe-creation-defaults' },
            { text: 'Hooks and filters', link: 'hooks-and-filters' },
      ]
    },
    {
      text: 'Roadmap', base: '/en/documentation/roadmap/',
        collapsed: true,
      items: [
        { text: 'Overview of releases and planned further development', link: 'release-overview' }
      ]
    },
      {
          text: 'Frequently Asked Questions (FAQ)', base: '/en/documentation/faq/',
          link: '/',
          collapsed: true,
          items: [
              { text: 'The site is very slow', link: 'site-slow' },
              { text: 'Problems and answers', link: 'problems-and-answers' },
              { text: 'How do I show the booking comment on the page and in the email?', link: 'booking-comment-emails' },
              { text: 'How do I show lock codes in emails?', link: 'lock-codes-email' },
              { text: 'How can I make the item detail page clearer?', link: 'organise-article-page' },
              { text: 'How do I prevent spam registrations?', link: 'avoid-spam-registrations'}
          ]
      },
  ]
}


