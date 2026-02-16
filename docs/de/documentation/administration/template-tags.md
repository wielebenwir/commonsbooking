#  Template Tags und Platzhalter für E-Mail-Vorlagen


Die folgende Seite zeigt dir, wie du die Wordpress Templates und die automatischen E-Mail Nachrichten mit Daten aus Wordpress bzw.
dem Plugin anreichern kannst.

Folgende Use-Cases werden dabei abgedeckt:
* Persönliche Ansprache der User in einer Bestätigungs E-Mails
* Schlüssel-Code zum Öffnen eines Fahrrad-Schloss in einer Bestätigungs-Email
* Fach- oder Regalnummer eines Artikels

## Template-Tags und Meta-Felder

Template-Tags kannst du in den E-Mail-Vorlagen oder Frontend-Templates
einsetzen.
Diese sog. Tags helfen dir auf Daten des Plugins, z.B. den Namen eines Artikel oder das Datum einer Buchung zugreifen zu können.
Ein Template-Tag für den Zugriff auf den Namen eines Artikel sieht z.B. so aus: <span v-pre>`{{item:post_title}}`</span>

::: tip Tipp
Für einige Daten haben wir bereits entsprechende Funktionen hinterlegt, die z.B. eine formatiertes Buchungsdatum ausgeben.
Weiter unten findest du eine vollständige Liste aller Tags des Plugins.
Grundsätzlich kannst du mit dem Template-Tags auf alle `wp_post` , `wp_postmeta` sowie die `user` und `user_meta` Daten zugreifen.
:::

Der generelle Zugriff auf die Werte hinter den Template-Tags funktioniert nach folgendem Schema:

* Über den ersten Teil definierst du zunächst, ob du Daten eines Artikels oder einer Station ausgeben möchtest. Artikel und Stationen sind in WordPress Costum Post Types und enthalten deshalb alle WordPress-Typischen Basisdaten wie title, status etc.
* Mit dem Teil nach dem `:` definierst du das anzuzeigende Feld. Dieses kann entweder ein Feld aus der Tabelle `wp_post`
      oder `wp_postmeta` sein. Unsere Template-Funktion erkennt dies automatisch und fügt den entsprechenden Wert ein.
* **Ein Beispiel:** Du hast für den Typ Artikel noch ein ein weiteres Feld in postmeta angelegt, um z.B. eine Auskunft
      über den Zustand des Artikels zu geben. Du legst also im WordPress-Editor ein weiteres benutzerdefiniertes Feld an, z.B. mit den Namen `condition`.
      Auf dieses Feld kannst du folgendermaßen zugreifen:
      - in E-Mail-Template: über <span v-pre>`{{item:condition}}`</span>
      - in Frontend-Templates (im Ordner `/template`) über folgende Funktion:
        ```php
        <?php echo CB::get('item', 'condition'); ?>
        ```

* Für User funktioniert dies nach dem gleichen Prinzip. Hast du z.b. über ein User-Profil-Plugin wie WP Members etc. weitere user_meta Felder (z.B. Straße, Telefonnummer) angelegt, kannst du auf diese Felder über <span v-pre>`{{user:feldname}}`</span> bzw. über
    ```php
    <?php echo CB::get(‘user’, ‘feldname’); ?>
    ```
    zugreifen.
  * Buchungen funktionieren nicht nach diesem Schema, da hier einige Besonderheiten bestehen. Um weitere Buchungsdaten ausgeben zu können, benötigst du Programmierkenntnisse. Falls dir hier etwas fehlt, schrieb uns bitte. Wir schauen, was wir möglich machen können.

## Liste der CB Tags

Folgende Template-Tags sind in den standardmäßig bei der Installation angelegten Vorlagen enthalten.

| Feld                                                                                                                |                Template-Tag                |
|---------------------------------------------------------------------------------------------------------------------|:------------------------------------------:|
| **User**                                                                                                            |                                            |
| Vorname:                                                                                                            |           <span v-pre>`{{user:first_name}}`</span>            |
| Nachname                                                                                                            |            <span v-pre>`{{user:last_name}}`</span>            |
| Email                                                                                                               |           <span v-pre>`{{user:user_email}}`</span>            |
| **Artikel**                                                                                                         |                                            |
| Name des Artikels                                                                                                   |           <span v-pre>`{{item:post_title}}`</span>            |
| **Station**                                                                                                         |                                            |
| Name der Standort                                                                                                   |         <span v-pre>`{{location:post_title}}`</span>          |
| Adresse der Station                                                                                                 |      <span v-pre>`{{location:formattedAddress}}`</span>       |
| Kontaktdaten der Station                                                                                            | <span v-pre>`{{location:formattedContactInfoOneLine}}`</span> |
| **Buchung**                                                                                                         |                                            |
| Anfang der Buchung                                                                                                  |        <span v-pre>`{{booking:pickupDatetime}}`</span>        |
| Ende der Buchung                                                                                                    |        <span v-pre>`{{booking:returnDatetime}}`</span>        |
| Zusammengefasster Buchungs-Zeitraum (z.B. vom 24. Januar 16:00 Uhr bis 26. Januar 12:00 Uhr)                 |     <span v-pre>`{{booking:formattedBookingDate}}`</span>     |
| Abholinformationen                                                                                                  |     <span v-pre>`{{location:pickupInstructions}}`</span>      |
| Link zur Buchung/Stornierung                                                                                        |         <span v-pre>`{{booking:bookingLink}}`</span>          |
| Buchungs-Codes (nur bei tageweise Buchung)                                                                          |     <span v-pre>`{{booking:formattedBookingCode}}`</span>     |
| Buchungskommentar                                                                                                   |        <span v-pre>`{{booking:returnComment}}`</span>         |
| **Einschränkungen**: Es sind die Templates Tags von User, Artikel, Station und Buchung sowie folgende möglich |                                            |
| Startdatum der Einschränkung inkl. Uhrzeit                                                                          |  <span v-pre>`{{restriction:formattedStartDateTime}}`</span>  |
| Voraussichtliches Enddatum der Einschränkung inkl. Uhrzeit                                                          |   <span v-pre>`{{restriction:formattedEndDateTime}}`</span>   |
| Hinweistext, der in der Einschränkung eingegeben wurde                                                              |           <span v-pre>`{{restriction:hint}}`</span>           |

##  Andere Metafelder

Bei der Verwendung von CommonsBooking in Kombination mit anderen Plugins muss deren Plugin-Präfix für Meta-Felder
genutzt werden, damit diese korrekt referenziert werden. Es folgt eine nicht vollständige Liste:

* **User (Plugin UsersWP):**
  Für neu angelegte Felder in UsersWP den Prefix `uwp_meta_` verwenden: <span v-pre>`{{user:uwp_meta_address}}`</span>

##  Eigene Metafelder für Standorte und Artikel verwenden

Ihr könnt weitere Felder für Standorte oder Artikel anlegen.

  * Dazu in den Einstellungen -> Tab “Erweitert” auswählen
  * im Feld Meta-Daten die gewünschten Felder nach der dort benannten Syntax anlegen. Die Erläuterung zur Syntax findet ihr in der Feldbschreibung.
  * z.B. `item;ItemKeyCode;Schloss-Code;text;Code` für das Zahlenschloss
  * Dieses Metafeld könnt ihr nun über die oben genannten Shortcodes in den E-Mail-Vorlagen einsetzen.
  * Beispiel: <span v-pre>`{{ [Der Code für das Zahlenschloss lautet:] item:ItemKeyCode}}`</span>
  * Der Text in den eckigen Klammern `[ ]` dient als Beschreibungstext, der vor dem eigentlichen Metafeld ausgegeben wird. Der Vorteil hier ist, dass der Beschreibungstext inklusive des Werts nur ausgegeben wird, wenn das dynamische Feld einen Wert enthält. In diesem Beschreibungstext sind auch einfache HTML-Codes erlaubt (z.B. br, strong, etc.)

Das folgende Tutorial-Video zeigt das Vorgehen Schritt für Schritt:

<iframe width="100%" height="547" src="https://www.youtube.com/embed/f4rr77GpB9o" title="CommonsBooking Tutorial Metafelder" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
