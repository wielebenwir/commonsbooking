#  Zugriffsrechte vergeben (CB-Manager)

__

In WordPress dürfen die Nutzenden mit der Rolle "Administrator" meistens
alles, bei CommonsBooking sind auch die Personen mit dieser Rolle die, die
Artikel und Standorte anlegen, Zeitrahmen erstellen, Buchungen einsehen können
usw. Manchmal will man jedoch auch Rollen definieren, die nicht alles auf der
Webseite bearbeiten können sondern nur bestimmte Artikel oder Standorte. Dann
können sich Stationen z.B. selbst administrieren, ohne immer auf die / den
Administrator*in angewiesen zu sein.

CommonsBooking bietet die Möglichkeit sogenannte **CB-Manager** einzurichten,
diese können dann **NUR** bestimmte Artikel / Stationen verwalten. Das könnten
in der Praxis zum Beispiel die Mitarbeitenden des Ladens sein, bei dem ein
Artikel stationiert ist.

Folgende Dinge darf der CommonsBooking Manager:

  * Artikel, Stationen und Zeitrahmen anlegen
  * Wenn zugewiesen oder selbst angelegt: Bearbeiten von Artikel, Stationen und Zeitrahmen
  * Selbst angelegte Zeitrahmen können in den Papierkorb gelegt werden
  * Keine Rechte für Löschen bzw. in den Papierkorb legen von Artikeln und Stationen
  * Buchungen stornieren, die zu Artikel gehören die sie administrieren.

Auf den Artikel- und Stationsseiten können die Rechte zum Verwalten der
Artikel, Stationen und Buchungen vergeben werden. Hierfür können Personen, mit
der Rolle "CommonsBooking Manager" einzeln den Artikeln bzw. Station
hinzugefügt werden. Die Rolle wird von CommonsBooking automatisch in die
Benutzer _innenverwaltung von WordPress hinzugefügt und kann in der Verwaltung
einzelnen Personen zugeordnet werden ausgewählt werden. Die Zuordnung kann nur
durch Administrator_ innen erfolgen. CommonsBooking Manager können selbst
keine Rechte vergeben.

Auf den Artikel- und Stationsseiten können dann die administrierenden Personen
ausgewählt und hinzugefügt werden. Zur Auswahl stehen hier nur die Personen,
denen vorher die Rolle "CommonsBooking Manager" zugeordnet wurde.

Zugang zur Verwaltung erhalten die Administrierenden dann über denselben Link,
über den sich auch allgemeine Administrierende Zugang zum WordPress-Backend
erhalten.

Im Unterschied zur WordPress-Administor-Rolle darf die Rolle *CommonsBooking Manager* folgendes nicht:

  * Allgemeine Seiten bearbeiten
  * Plugins ändern
  * Das Design der Seite ändern
  * usw.

##  Zugriffsrechte anpassen

::: info Technische Information
Nur relevant für Entwickler oder Administratoren mit technischem Verständnis
:::

Für Fälle in denen die Standard-Berechtigungen nicht ausreichen, können z.B. Plugins wie *User Role Editor* genutzt
werden, um Rollen und Berechtigungen anzupassen.
Beispielsweise kann eine zweite Rolle hinzugefügt werden, welche einen eingeschränkeren Zugriff, als die CB-Manager Rolle hat.
Für diese Fälle dokumentiert der folgende Abschnitt, welche Rollen und Berechtigungen das CommonsBooking Plugin bereitstellt.

CommonsBooking benutzt weitesgehend die Konvention sog. [*CustomPostTypes*](https://developer.wordpress.org/plugins/post-types/)
in WordPress. Nur zum Zugriff bzw. Anzeigen der CustomPostTypes im Admin-Backend fügt das Plugin jeweils eine Berechtigung (siehe [Referenz-Tabelle weiter unten](#referenz-liste-der-capabilities)) hinzu.

Für die Wordpress-Administration sind nachfolgend einmal die CustomPostTypes, deren konvetionelle und die zusätzlichen Berechtigungen dokumentiert.

### Referenz-Liste der zusätzlichen User-Rollen

* **Rolle**: CommonsBooking Manager
  * WP Rollen *"slug"*: `cb_manager`
  * WP Capabilities:
    * Für diese Rolle **immer gesetzt**: `read`, `edit_posts` und `manage_commonsbooking`

Die für Wordpress standardmäßig bereits existierende Administrator-Rolle hat die gleichen Berechtigungen wie der `cb_manager` gesetzt.

### Referenz-Liste der CustomPostTypes

Auf dieser Seite werden oft die internen Namen für Artikel / Standorte /
Zeitrahmen / Buchungen etc. verwendet. Deshalb folgt eine Übersichtstabelle zu
den internen Namen und deren Bedeutung:

**Externer Name** |  **Interner Name**
---|---
Artikel  |  cb_items
Standorte  |  cb_locations
Zeitrahmen  |  cb_timeframes
Karten  |  cb_maps
Buchungen  |  cb_bookings
Einschränkungen  |  cb_restrictions

### Referenz-Liste der Capabilities

Hier sind die Namen der verschiedenen CommonsBooking spezifischen Wordpress-Berechtigungen
(auch [Capabilities](https://wordpress.org/documentation/article/roles-and-capabilities/)), welche einer Rolle
zugewiesen werden können:

**Berechtigung** |  **Bewirkt**
---|---
manage_commonsbooking  |  CommonsBooking Menüpunkt im Backend anklickbar (Vorraussetzung für alle anderen Berechtigungen)
manage_commonsbooking_cb_booking  |  Buchungen-Menüpunkt im Backend anzeigen
manage_commonsbooking_cb_item  |  Artikel-Menüpunkt im Backend anzeigen
manage_commonsbooking_cb_location  |  Standorte-Menüpunkt im Backend anzeigen
manage_commonsbooking_cb_map  |  Karten-Menüpunkt im Backend anzeigen
manage_commonsbooking_cb_restriction  |  Einschränkungen-Menüpunkt im Backend anzeigen
manage_commonsbooking_cb_timeframe  |  Zeitrahmen-Menüpunkt im Backend anzeigen

Diese Berechtigungen definieren erstmal NUR, ob der Menüpunkt im Backend für
die Administrierenden angezeigt wird. Das heißt noch nicht, dass die Rollen
auch die Artikel bearbeiten dürfen.

NUR wenn **alle** `manage_*` Berechtigungen deaktiviert sind, verschwindet auch
der "CommonsBooking" Reiter in den Optionen. Wenn zb. nur die
`manage_commonsbooking_cb_location` Berechtigung gesetzt ist sieht die
entsprechende Rolle zwar den Menüpunkt, kann aber nicht darauf zugreifen.

###  Bearbeitungs-Berechtigungen

Jede Art von Post (also Artikel / Standorte / Zeitrahmen / Karten /
Einschränkungen) hat eigenen Berechtigungen, die nach einem festen Schema
funktionieren. Da die Namen selbsterklärend sind, werden sie hier nicht näher
beschrieben, hier nur ein Screenshot von den Berechtigungen für einen Artikel.

![](/img/cb-manager-permissions.png)

Nur wenn die entsprechende Rolle auch die Berechtigung für eine Aktion
bekommen hat, kann sie auch diese durchführen. Das heißt also, dass z.B. die
`manage_commonsbooking_cb_item` Berechtigung zu vergeben wenig Sinn ergibt,
wenn nicht zumindest auch die `edit_cb_items` Berechtigung oder eine andere
Berechtigung für Artikel vergeben wird.

Hier auch besonders relevant ist die Berechtigung `edit_other_cb_bookings`.
Diese bestimmt, ob ein Manager in der Lage ist Buchungen von anderen Nutzenden zu stornieren.

###  Andere Rollen einem Artikel / Standort zuweisen

::: tip Ab Version 2.8.2
:::

Mithilfe eines Codeschnipsels kannst du eine weitere Rolle definieren, welche einem Artikel / Standort
zugewiesen werden kann und diesen dann entsprechend ihrer / seiner Berechtigung bearbeiten darf. Das
funktioniert mit einem sogenannten [Filter](/dokumentation/einstellungen/hooks-und-filter). Auf der Seite findest du mehr Infos zu Codeschnipseln.

Der Filter heißt `commonsbooking_manager_roles` und kann zum Beispiel wie folgt benutzt werden:

```php
add_filter('commonsbooking_manager_roles', 'add_manager' );
function add_manager( $array ){
    $array[]='editor';
    return $array;
}
```

Dieser Codeschnipsel würde die Rolle mit dem Namen ‘editor’ zu den Rollen
hinzufügen, die einem Artikel hinzugefügt werden können. Dabei ist es wichtig
den _slug_ der Rolle zu verwenden.

