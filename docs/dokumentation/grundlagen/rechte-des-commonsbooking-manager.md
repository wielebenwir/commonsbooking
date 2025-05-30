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

Folgende Dinge darf der CommonsBooking Manager nicht:

  * Allgemeine Seiten bearbeiten
  * Plugins ändern
  * Das Design der Seite ändern
  * usw.

##  Zugriffsrechte anpassen

Falls die angebotenen Berechtigungen nicht ausreichen oder du eine zweite
Rolle hinzufügen möchtest, die weniger kann als der CB Manager dann kannst du
Plugins nutzen um die Berechtigung von Nutzendenrollen anzupassen (z.B. User
Role Editor).

Zur Referenz: Hier werden oft die internen Namen für Artikel / Standorte /
Zeitrahmen / Buchungen etc. verwendet. Deshalb hier eine Übersichtstabelle zu
den internen Namen und deren Bedeutung

**Externer Name** |  **Interner Name**
---|---
Artikel  |  cb_items
Standorte  |  cb_locations
Zeitrahmen  |  cb_timeframes
Karten  |  cb_maps
Buchungen  |  cb_bookings
Einschränkungen  |  cb_restrictions



Hier sind die Namen der verschiedenen Berechtigungen, die einer Rolle gegeben
werden kann:

###  Management Berechtigungen

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

NUR wenn alle manage_xxx Berechtigungen deaktiviert sind, verschwindet auch
der "CommonsBooking" Reiter in den Optionen. Wenn zb. nur die
manage_commonsbooking_cb_location Berechtigung gesetzt ist sieht die
entsprechende Rolle zwar den Menüpunkt, kann aber nicht darauf zugreifen.

###  Bearbeitungs-Berechtigungen

Jede Art von Post (also Artikel / Standorte / Zeitrahmen / Karten /
Einschränkungen) hat eigenen Berechtigungen, die nach einem festen Schema
funktionieren. Da die Namen selbsterklärend sind, werden sie hier nicht näher
beschrieben, hier nur ein Screenshot von den Berechtigungen für einen Artikel.

![](/img/2d6feefe59ddd3bb9e59ea4a0789488f.png)

Nur wenn die entsprechende Rolle auch die Berechtigung für eine Aktion
bekommen hat, kann sie auch diese durchführen. Das heißt also, dass z.B. die
manage_commonsbooking_cb_item Berechtigung zu verleihen wenig Sinn ergibt,
wenn nicht zumindest auch die edit_cb_items Berechtigung oder eine andere
Berechtigung für Artikel verliehen wird.

Hier auch besonders relevant ist die Berechtigung **edit_other_cb_bookings** .
Diese bestimmt, ob ein Manager in der Lage ist Buchungen von anderen Nutzenden
zu stornieren.

###  Andere Rollen einem Artikel / Standort zuweisen (Ab 2.8.2)

Es ist mit einem kleinen Codeschnipsel möglich, auch eine weitere Rolle zu
definieren die einem Artikel / Standort zugewiesen werden kann und diesen dann
entsprechend ihrer / seiner Berechtigung bearbeiten darf. Das funktioniert mit
einem [ Filter ](/dokumentation/einstellungen/hooks-und-filter/) (Dort findest du auch
mehr Infos zu Codeschnipseln). Dieser heißt _commonsbooking_manager_roles_ und
kann zum Beispiel wie folgt benutzt werden:

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

