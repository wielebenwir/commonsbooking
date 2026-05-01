# Häufige Fragen (FAQ)

## Wie bekomme ich den Buchungskommentar auf die Seite und in die Email?

::: details Antwort anzeigen
In den Einstellungen kannst du die Buchungskommentare aktivieren. In den E-Mail-Vorlagen musst du dann folgenden Code einfügen: <span v-pre>`{{booking:returnComment}}`</span>
:::

## Wie kann ich die Artikeldetailseite übersichtlicher gestalten?

::: details Antwort anzeigen
Lange Artikelseiten bedeuten, dass Menschen lange bis zum Buchungskalender scrollen müssen.

Hier empfiehlt sich ein Plugin wie [Show-Hide/Collapse-Expand](https://de.wordpress.org/plugins/show-hidecollapse-expand) zu nutzen, mit dem Informationen eingeklappt werden können.

![](/img/item-collapse.png)
:::

## Meine Seite ist sehr langsam – was kann ich tun?

:::: details Antwort anzeigen
Wenn deine CommonsBooking Seite sehr langsam ist, kann das verschiedene Gründe haben.
Wir nutzen eine Technologie namens Caching, mit der wir häufig gestellte Anfragen in einem Zwischenspeicher
zurückhalten, um Serverkapazitäten einzusparen.

Das Caching kann unter Umständen nicht funktionieren, wenn:

  * [WP_DEBUG](https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/) aktiviert ist, dafür musst du deine wp-config.php bearbeiten
  * Der Ordner /tmp/ auf deinem Server nicht beschreibbar ist. Falls dies der Fall sein sollte, dann kontaktiere bitte deinen Webhoster und bitte ihn darum den Ordner beschreibbar zu machen.
    * Falls das nicht möglich sein sollte, kannst du in den CommonsBooking Einstellungen unter "Erweiterte Optionen" den Pfad für den Dateisystem-Cache einstellen. Bitte frage bei deinem Webhoster nach, welche Ordner auf dem Server für temporäre Dateien verfügbar sind.
    * Falls das auch nicht möglich sein sollte: Gehe zu deiner [Webseiten-Info](https://wordpress.org/documentation/article/site-health-screen/) unter (http://DEINE-URL/wp-admin/site-health.php?tab=debug).
       Dort findest du unter **Verzeichnisse** den Pfad deines WordPress-Verzeichnisses. Wähle alternativ einen Ordner im Format `DEIN_VERZEICHNIS/symfony` als Cache-Ziel.
      :::danger Achtung
      Dies kann dazu führen, dass dein WordPress-Verzeichnis sehr groß wird.
      :::

Alternativ kannst du auch [REDIS](https://redis.io) auf deinem Server installieren und den Cache durch REDIS verwalten lassen.
Da REDIS den Cache im RAM speichert, statt im Dateisystem, ist das meistens etwas schneller.
::::

## Wie kann ich Konfigurationsprobleme debuggen?

:::: details Antwort anzeigen
:::warning Technische Expertise nötig!
:::

Mit dem Plugin `query-monitor` kannst du Anfragen deiner Seite live überwachen. Damit ist es z.B. möglich einen
falsch konfigurierten Cache schnell zu identifizieren.
::::

## Wie füge ich Zahlenschloss-Codes in E-Mails ein?

::: details Antwort anzeigen
Eine häufige Frage ist, ob Codes für Zahlenschlösser an Artikeln oder Standorten hinzugefügt werden können, um sie in den versendeten E-Mails anzuzeigen.

Das funktioniert über sog. Meta-Felder, welche Artikel und Standort zugewiesen werden. Diese Felder können dann auch in den E-Mail-Templates verwendet werden.

Der Artikel [Template-Tags](../administration/template-tags#eigene-metafelder-fur-standorte-und-artikel-verwenden) der Dokumentation enthält eine ausführliche Anleitung.
:::

## Wie sperre ich einzelne Nutzer vorübergehend?

::: details Antwort anzeigen
Möchtest du Nutzende vorübergehend für einen bestimmten Zeitraum sperren, weil diese z.B. die Ausleihe üdermäßig nutzen oder gegen Ausleihbedingungen verstoßen?

Wir empfehlen, ein dediziertes WordPress-Plugin zum Blockieren von Nutzenden zu verwenden. Wenn deine Nutzenden auf der Seite sowieso nichts anderes tun können als buchen, ist das auch die einfachste Lösung.

Das Plugin [User Blocker](https://wordpress.org/plugins/user-blocker/) wurde getestet und funktioniert problemlos, auch mit einer Zeitschaltfunktion. Es gibt weitere Plugins mit dieser Funktionalität.

Das Blockieren bestimmter Benutzergruppen ist kein eingebautes Feature von CommonsBooking und wird wahrscheinlich auch in naher Zukunft nicht kommen, da es bereits WordPress-Plugins gibt, die genau das abdecken.
:::

## Wie erlaube ich Buchungen über geschlossene Tage hinweg?

::: details Antwort anzeigen
Wenn du deinen Nutzenden ermöglichen möchtest, einen Artikel über geschlossene Tage hinweg zu buchen (z.B. über das Wochenende, wenn die Station geschlossen ist), kannst du das in den Standort-Einstellungen konfigurieren.

Ausführliche Informationen findest du unter [Standorte anlegen](../first-steps/create-location).
:::

## Wie erhöhe ich die Anzahl der Artikel in der cb_items-Liste?

::: details Antwort anzeigen
Die Anzahl der Artikel pro Seite wird über die globalen WordPress-Leseeinstellungen übernommen.

So änderst du sie:

1. Als WordPress-Administrator einloggen
2. **Einstellungen -> Lesen** aufrufen
3. Den Wert bei **Blogseiten zeigen maximal** anpassen
:::

## Wie verhindere ich Spam-Registrierungen?

::: details Antwort anzeigen
Dafür gibt es verschiedene Möglichkeiten (Vorschläge aus der Community):

* Ein HoneyPot lenkt Bots ab, ohne Menschen zu nerven: [Honeypot Plugin](https://wordpress.org/plugins/honeypot/)

* "Ich hatte mal für **UltimateMember ein winziges Plugin geschrieben mit dem man einfach einen Text eingeben muss um sich zu registrieren**. Barrierearm und hält alle Bots ab: [Download von Github](https://github.com/hansmorb/um-captchaquiz/raw/refs/heads/master/um-captchaquiz.zip). Dazu einfach eine Textbox erstellen und den Metaschlüssel in den Plugin Einstellungen eintragen."

* "Wir nutzen Hcaptcha für wordpress. Nach der Installation in den Einstellungen des Plugins die von euch genutzte Registrierung (z.B. UltimateMember) auswählen. Für die Nutzung muss ein Hcaptcha Account erstellt werden. Sie werben mit Privacy-First und das keine Nutzerdaten verkauft werden. Ich selber habe das nicht geprüft." - [Download aus dem Plugin-Verzeichnis](https://wordpress.org/plugins/hcaptcha-for-forms-and-more)
:::

## Plugin- und Theme-Inkompatibilitäten

### Lightstart

::: details Antwort anzeigen
Treten Probleme bei der Anzeige des Kalenders im Admin-Bereich der Buchungen auf (sog. Admin-Backend), kann eine mögliche Lösung sein, das [Plugin "Lightstart" (wp-maintenance-mode)](https://wordpress.org/plugins/wp-maintenance-mode) zu deaktivieren oder zu entfernen und neu zu installieren. Das Problem ist eine Inkompatibilität von Lightstart mit CommonsBooking und kein Fehler im Code von CommonsBooking. Das Problem tritt nicht mehr auf, wenn eine Neuinstallation von Lightstart vorgenommen wurde. Mehr dazu auf [Github im CommonsBooking Quellcode-Repository](https://github.com/wielebenwir/commonsbooking/issues/1646).

![](/img/backend-booking-list-bug.png)
:::

### GridBulletin

::: details Antwort anzeigen
In der letzten Version von [GridBulletin](https://wordpress.org/themes/gridbulletin) kommt es zu einer Inkompatibilität mit CommonsBooking. Probleme tauchen auf, wenn der Footer aktiviert ist. Konkrete Probleme sind z.B. das Fehlen des Buchungs-Kalenders auf der Artikelseite. Aus technischer Sicht liegt es daran, dass die nötigen Javascript-Quellen von CommonsBooking nicht ausgeliefert werden. Der Grund innerhalb des GridBulletin Themes oder eine Lösung konnte bisher nicht gefunden werden.
:::

### All-in-one-Event

:::: details Antwort anzeigen
:::info Behoben seit 2.7.2 (06.2022)
Für Experten siehe [Issue 675](https://github.com/wielebenwir/commonsbooking/issues/675)
:::

Leider kommt es bei der gleichzeitigen Nutzung des Plugins "All-in-one-Event" zu Fehlern, sodass Seiten, die von CommonsBooking erzeugt werden, nicht angezeigt werden.

Die Ursache ist leider auf eine schlechte Programmierung des All-in-one-Event-Plugins zurückzuführen, welches sich nicht an die Wordpress-Standards hält und so tief in Wordpress eingreift, dass es die Programmlogik von CommonsBooking quasi überschreibt.

Wir haben einiges versucht, um eine parallele Nutzung zu ermöglichen, leider aber bisher keine Lösung gefunden.

Wenn ihr das Problem auch habt, schreibt gerne direkt an den Support des Plugins, vielleicht passen sie ihr Plugin doch noch irgendwann an.
::::

### REDIS Object Cache

::: details Antwort anzeigen
Im Zusammenhang mit dem [Cache](../advanced-functionality/) gab es in der Vergangenheit bereits Probleme mit anderen Wordpress-Plugins wie z.B. 'REDIS Object Cache'. Aus diesem Grund raten wir von der Nutzung solcher Plugins ab.

Die von CommonsBooking generierten Seiten sollten von der Optimierung durch Dritt-Plugins ausgenommen werden. CommonsBooking verwendet ein eigenes Caching.
:::

### Ultimate Member

::: details Antwort anzeigen
Wenn ihr das Plugin Ultimate Member benutzt und die Benutzerrolle "CommonsBooking Manager" nutzen möchtet, müsst ihr in Ultimate Member für die Rolle `cb_manager` noch ein Häkchen setzen, um diese für den Admin-Zugang zu aktivieren.
:::

### Autoptimize / Caching-Plugins

:::: details Antwort anzeigen
Optimierungs-Plugins oder weitere Caching-Plugins können dazu führen, dass CommonsBooking nicht alle Seiten korrekt anzeigen kann.

Betroffene Plugins (nicht vollständig):
* Autoptimize

Die von CommonsBooking generierten Seiten sollten von der Optimierung durch Dritt-Plugins ausgenommen werden. CommonsBooking verwendet ein eigenes Caching.

:::info Hast du ein Problem festgestellt?
Dann trage die Inkompatibilität hier ein!
:::
::::
