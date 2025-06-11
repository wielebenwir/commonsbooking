#  Die Seite ist sehr langsam

__

Wenn deine CommonsBooking Seite sehr langsam ist, kann das verschiedene Gründe haben.
Wir nutzen eine Technologie namens Caching, mit der wir häufig gestellte Anfragen in einem Zwischenspeicher
zurückhalten, um Serverkapazitäten einzusparen.

Das Caching kann unter Umständen nicht funktionieren, wenn:

  * [WP_DEBUG](https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/) aktiviert ist, dafür musst du deine wp-config.php bearbeiten
  * Der Ordner /tmp/ auf deinem Server nicht beschreibbar ist. Falls dies der Fall sein sollte, dann kontaktiere bitte deinen Webhoster und bitte ihn darum den Ordner beschreibbar zu machen.
    * Falls das nicht möglich sein sollte, kannst du in den CommonsBooking Einstellungen unter “Erweiterte Optionen” den Pfad für den Dateisystem-Cache einstellen. Bitte frage bei deinem Webhoster nach, welche Ordner auf dem Server für temporäre Dateien verfügbar sind.
    * Falls das auch nicht möglich sein sollte: Gehe zu deiner [Webseiten-Info](https://wordpress.org/documentation/article/site-health-screen/) unter (http://DEINE-URL/wp-admin/site-health.php?tab=debug).
       Dort findest du unter **Verzeichnisse** den Pfad deines WordPress-Verzeichnisses. Wähle alternativ einen Ordner im Format `DEIN_VERZEICHNIS/symfony` als Cache-Ziel.
      ::: danger Achtung
      Dies kann dazu führen, dass dein WordPress-Verzeichnis sehr groß wird.
      :::

Alternativ kannst du auch [REDIS](https://redis.io) auf deinem Server installieren und den Cache durch REDIS verwalten lassen.
Da REDIS den Cache im RAM speichert, statt im Dateisystem, ist das meistens etwas schneller.

## Troubleshooting

::: warning Technische Expertise nötig!
:::

Mit dem Plugin `query-monitor` kannst du Anfragen deiner Seite live überwachen. Damit es es z.B. möglich ein
falsch konfigurierten Cache schnell zu identifizieren.

