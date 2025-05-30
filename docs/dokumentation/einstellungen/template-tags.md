#  Template Tags und Platzhalter für E-Mail-Vorlagen

__

Template-Tags kannst du in den E-Mail-Vorlagen oder Frontend-Templates
einsetzen.
Grundsätzlich funktionieren Template-Tags folgendermaßen:
<div v-pre>
  * Für einige Daten haben wir bereits entsprechende Funktionen hinterlegt, die z.B. eine formatiertes Buchungsdatum ausgeben.
  * Grundsätzlich kannst du mit dem Template-Tags aber auf alle `wp_post` , `wp_postmeta` sowie die `user` und `user_meta` Daten zugreifen. Dies funktioniert nach folgendem Schema:
    * Über den ersten Teil definierst du zunächst, ob du Daten eines Artikels oder einer Station ausgeben möchtest. Artikel und Stationen sind in WordPress Costum Post Types und enthalten deshalb alle WordPress-Typischen Basisdaten wie title, status etc.
    * Mit dem Teil nach dem `:` definierst du das anzuzeigende Feld. Dieses kann entweder ein Feld aus der Tabelle `wp_post`
      oder `wp_postmeta` sein. Unsere Template-Funktion erkennt dies automatisch und fügt den entsprechenden Wert ein.
    * Ein Beispiel: Du hast für den Typ Artikel noch ein ein weiteres Feld in postmeta angelegt, um z.B. eine Auskunft
      über den Zustand des Artikels zu geben. Du legst also im WordPress-Editor ein weiteres benutzerdefiniertes Feld an, z.B. mit den Namen `condition`.

      Auf dieses Feld kannst du folgendermaßen zugreifen:
      - in E-Mail-Template: über `{{item:condition}}`
      - in Frontend-Templates (im Ordner `/template`) über folgende Funktion:
        ```php
        <?php echo CB::get('item', 'condition'); ?>
        ```
  * Für User funktioniert dies nach dem gleichen Prinzip. Hast du z.b. über ein User-Profil-Plugin wie WP Members etc. weitere user_meta Felder (z.B. Straße, Telefonnummer) angelegt, kannst du auf diese Felder über `{{user:feldname}}` bzw. `<?php echo CB::get(‘user’, ‘feldname’); ?>` zugreifen.
  * Buchungen funktionieren nicht nach diesem Schema, da hier einige Besonderheiten bestehen. Um weitere Buchungsdaten ausgeben zu können, benötigst du Programmierkenntnisse. Falls dir hier etwas fehlt, schrieb uns bitte. Wir schauen, was wir möglich machen können.
</div>
Folgende Template-Tags sind in den standardmäßig bei der Installation angelegten Vorlagen enthalten.

<div v-pre>
| Feld                                                                                                                |                Template-Tag                |
|---------------------------------------------------------------------------------------------------------------------|:------------------------------------------:|
| **User**                                                                                                            |                                            |
| Vorname:                                                                                                            |           `{{user:first_name}}`            |
| Nachname                                                                                                            |            `{{user:last_name}}`            |
| Email                                                                                                               |           `{{user:user_email}}`            |
| **Artikel**                                                                                                         |                                            |
| Name des Artikels                                                                                                   |           `{{item:post_title}}`            |
| **Station**                                                                                                         |                                            |
| Name der Standort                                                                                                   |         `{{location:post_title}}`          |
| Adresse der Station                                                                                                 |      `{{location:formattedAddress}}`       |
| Kontaktdaten der Station                                                                                            | `{{location:formattedContactInfoOneLine}}` |
| **Buchung**                                                                                                         |                                            |
| Anfang der Buchung                                                                                                  |        `{{booking:pickupDatetime}}`        |
| Ende der Buchung                                                                                                    |        `{{booking:returnDatetime}}`        |
| Zusammengefasster Buchungs-Zeitraum (z.B. vom 24. Januar 16:00 Uhr bis 26. Januar 12:00 Uhr)                 |     `{{booking:formattedBookingDate}}`     |
| Abholinformationen                                                                                                  |     `{{location:pickupInstructions}}`      |
| Link zur Buchung/Stornierung                                                                                        |         `{{booking:bookingLink}}`          |
| Buchungs-Codes (nur bei tageweise Buchung)                                                                          |     `{{booking:formattedBookingCode}}`     |
| Buchungskommentar                                                                                                   |        `{{booking:returnComment}}`         |
| **Einschränkungen**: Es sind die Templates Tags von User, Artikel, Station und Buchung sowie folgende möglich |                                            |
| Startdatum der Einschränkung inkl. Uhrzeit                                                                          |  `{{restriction:formattedStartDateTime}}`  |
| Voraussichtliches Enddatum der Einschränkung inkl. Uhrzeit                                                          |   `{{restriction:formattedEndDateTime}}`   |
| Hinweistext, der in der Einschränkung eingegeben wurde                                                              |           `{{restriction:hint}}`           |
</div>

##  Andere Metafelder

Bei der Verwendung von CommonsBooking in Kombination mit anderen Plugins muss deren Plugin-Präfix für Meta-Felder
genutzt werden, damit diese korrekt referenziert werden. Es folgt eine nicht vollständige Liste:

**User (Plugin UsersWP):**
Für neu angelegte Felder in UsersWP den Prefix `uwp_meta_` verwenden: <div v-pre>`{{user:uwp_meta_address}}`</div>

##  Eigene Metafelder für Standorte und Artikel verwenden

Ihr könnt weitere Felder für Standorte oder Artikel anlegen.

  * Dazu in den Einstellungen -> Tab “Erweitert” auswählen
  * im Feld Meta-Daten die gewünschten Felder nach der dort benannten Syntax anlegen. Die Erläuterung zur Syntax findet ihr in der Feldbschreibung.
  * z.B. `item;ItemKeyCode;Schloss-Code;text;Code` für das Zahlenschloss
  * Dieses Metafeld könnt ihr nun über die oben genannten Shortcodes in den E-Mail-Vorlagen einsetzen.
  * Beispiel: <div v-pre>`{{ [Der Code für das Zahlenschloss lautet:] item:ItemKeyCode}}`</div>
  * Der Text in den eckigen Klammern `[ ]` dient als Beschreibungstext, der vor dem eigentlichen Metafeld ausgegeben wird. Der Vorteil hier ist, dass der Beschreibungstext inklusive des Werts nur ausgegeben wird, wenn das dynamische Feld einen Wert enthält. In diesem Beschreibungstext sind auch einfache HTML-Codes erlaubt (z.B. br, strong, etc.)

