# Überblick für Entwickler


::: danger 
Dieser Bereich ist noch in Arbeit
:::

## Zusammenfassung

Dazu erweitert das Plugin mit sog. [Custom-Post-Types]() den allgemeinen Funktions-Umfang eines [WP-Posts]() 
und erlaubt es Artikel, Stationen, Zeitrahmen wie ein Wordpress-Posting zu betrachten und dementrsprechend
programmatisch zu erweitern.

In deiner Wordpress-Applikation kannst du mit den Daten des Plugins entweder über [Shortcodes]() oder über [Filter oder Action-Hooks]() nutzen bzw. mit ihnen interagieren. Dabei nutzt du entweder die bestehenden Hooks von Wordpress-Core (bzw. Wordpress-REST-API) oder kannst in die vom CommonsBooking Plugin implementierten Hooks einhaken.

```
       "Core"            
   |-----------|              "Plugins"                  "User"
   | Wordpress |
   |   Core    |<--extends-- CommonsBooking <---uses---- Verleihe
   |-----------|                    |
       /\                           |
       |                         implements 
       |                            |
       |                           \/
    ( Wordpress ) <---extends--- Commons-API <--------- Aggregator-
    ( REST API  )             __   GBFS                   Platforms
      /\                       /\                    (Maps, Listings, ...)
      |                       /
      |                      /
         (Any Client-App)
     
```
