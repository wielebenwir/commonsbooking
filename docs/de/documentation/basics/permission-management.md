#  Zugriffsrechte vergeben (CB-Manager)


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
  * Selbst angelegte Zeitrahmen können in den Papierkorb gelegt werden (nicht die anderer Personen)
  * Artikel und Stationen können nur dann in den Papierkorb gelegt werden, wenn sie selbst angelegt wurden. Artikel und Stationen, die dem Manager lediglich zugewiesen sind, können nicht gelöscht werden.
  * Einschränkungen für Artikel und Stationen anlegen und bearbeiten, die sie administrieren. Einschränkungen können nur dann in den Papierkorb gelegt werden, wenn sie selbst angelegt wurden.
  * Buchungen stornieren, die zu Artikel gehören die sie administrieren.

Auf den Artikel- und Stationsseiten können die Rechte zum Verwalten der
Artikel, Stationen und Buchungen vergeben werden. Hierfür können Personen, mit
der Rolle "CommonsBooking Manager" einzeln den Artikeln bzw. Station
hinzugefügt werden. Die Rolle wird von CommonsBooking automatisch in die
Benutzer _innenverwaltung von WordPress hinzugefügt und kann in der Verwaltung
einzelnen Personen zugeordnet werden ausgewählt werden. Die Zuordnung kann nur
durch Administrator_ innen erfolgen. CommonsBooking Manager können selbst
keine Rechte vergeben.

Auf den Artikel- und Stationsseiten können dann die Manager
ausgewählt und hinzugefügt werden. Zur Auswahl stehen hier nur die Personen,
denen vorher die Rolle "CommonsBooking Manager" zugeordnet wurde.

Zugang zur Verwaltung erhalten die Manager dann über denselben Link,
über den sich auch allgemeine Administrierende Zugang zum WordPress-Backend
erhalten.

## Administrator vs CommonsBooking Manager
In der folgenden Tabelle ist exemplarisch dargestellt, was ein CommonsBooking Manager darf im Gegensatz zu einem Administrator.

**Funktion** |  **Administrator** |  **CommonsBooking Manager**
---|---|---
Artikel / Standorte anlegen  |  Ja  |  Ja
Artikel / Standorte bearbeiten  |  Ja  |  Ja (nur wenn zugewiesen)
Artikel / Standorte löschen  |  Ja  |  Nur selbst angelegte (nicht, wenn nur zugewiesen)
Zeitrahmen anlegen  |  Ja  |  Ja (nur mit zugewiesenen Artikeln / Standorten)
Zeitrahmen bearbeiten  |  Ja  |  Ja (nur mit zugewiesenen Artikeln / Standorten)
Zeitrahmen löschen  |  Ja  |  Nein (nur selbst angelegte)
Buchungen stornieren  |  Ja  |  Ja (nur mit zugewiesenen Artikeln / Standorten)
Einschränkungen anlegen / bearbeiten  |  Ja  |  Ja (nur mit zugewiesenen Artikeln / Standorten)
Einschränkungen löschen  |  Ja  |  Nur selbst angelegte
Karten anlegen / bearbeiten / löschen  |  Ja  |  Ja (**alle** Karten, siehe [unten](#karten))

Darüber hinaus hat der CommonsBooking Manager keine besonderen Berechtigungen was andere Teile der Webseite betrifft.
Folgende Dinge darf der CommonsBooking Manager zum Beispiel nicht:

  * Allgemeine Seiten bearbeiten
  * Plugins ändern
  * Das Design der Seite ändern
  * usw.

## Einschränkungen {#einschraenkungen}

In der Liste der Einschränkungen im Backend sehen Manager nur die Einschränkungen, die zu Artikeln oder Standorten gehören, die sie administrieren.
Beim Löschen verhalten sich Einschränkungen wie Artikel: Ein Manager kann nur Einschränkungen in den Papierkorb legen, die er selbst angelegt hat.

## Karten {#karten}

Manager sehen den Menüpunkt "Karten" und können Karten anlegen, bearbeiten und löschen. Anders als Artikel, Standorte und Einschränkungen können Karten nicht einzelnen Managern zugewiesen werden.
Ein CommonsBooking Manager kann daher **alle** Karten der Webseite bearbeiten und löschen, auch die, die von Administrator*innen angelegt wurden.
Wenn das nicht gewünscht ist, sollte die Rolle CB-Manager nicht an Personen vergeben werden, die Karten nicht ändern dürfen, oder diesen Personen sollte eine eigene Rolle zugewiesen werden (siehe [Zugriffsrechte anpassen](#zugriffsrechte-anpassen)).

::: warning
Die Karten-Berechtigungen sollten nicht von der Rolle `cb_manager` selbst entfernt werden. CommonsBooking fügt die Berechtigungen dieser Rolle nach Plugin-Updates wieder hinzu, die Änderung würde also rückgängig gemacht.
Eine eigene, selbst konfigurierte Rolle ist davon nicht betroffen.
:::

## CommonsBooking Manager zum Manager für alle Artikel / Standorte machen {#filterhook-isUserAdmin}

Mithilfe eines [Filter Hooks](../advanced-functionality/hooks-and-filters) kannst du eine bestimmte Rolle so einstellen,
dass sie automatisch Manager für alle Artikel / Standorte wird. Das unten genannte Beispiel macht das mit der Rolle 'cb_manager', also konfiguriert den CB-Manager so, dass er automatisch allen Artikeln und Standorten der Instanz zugerechnet wird. Falls das mit einer anderen Rolle passieren soll, muss diese Rolle auch den Manager Rollen mit einem [weiteren Codeschnipsel hinzugefügt werden](#filterhook-manager-roles).

```php
add_filter(
	'commonsbooking_isUserAdmin',
	function ( bool $isAdmin, WP_User $user ) {
		return in_array( 'cb_manager', $user->roles, true ) ? true : $isAdmin;
	},
	10,
	2
);
```



## Rollen von Buchungsregeln ausnehmen {#filterhook-privileged-roles}

Manche Personen sollen unabhängig von den [Buchungsregeln](../first-steps/setup-bookingrules) buchen können, zum Beispiel um Buchungen für andere vorzunehmen.
Folgende Personen sind immer von den Buchungsregeln ausgenommen:

  * Administrator*innen
  * CommonsBooking Manager (und andere Nutzende), die den Artikel oder den Standort der Buchung bearbeiten dürfen, zum Beispiel weil sie zugewiesen sind oder ihn selbst angelegt haben

Weitere Rollen können mit dem Filter `commonsbooking_privileged_roles` ausgenommen werden. Im Gegensatz zu den Manager-Rollen oben erhält die Rolle dadurch keine zusätzlichen Bearbeitungsrechte, ihre Mitglieder sind lediglich von den Buchungsregeln ausgenommen.
Das Beispiel fügt die Rolle "Redakteur" (Slug `editor`) hinzu:

```php
add_filter(
  'commonsbooking_privileged_roles',
  function ( array $privilegedRoles ) {
    $privilegedRoles[] = 'editor';
    return $privilegedRoles;
  }
);
```

Es muss der _Slug_ der Rolle verwendet werden. Mehr dazu: [Bestimmte Rollen grundsätzlich von allen Buchungsregeln ausnehmen](../first-steps/setup-bookingrules).

##  Zugriffsrechte anpassen {#zugriffsrechte-anpassen}

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

![](/img/cb-manager-permissions.png)

Nur wenn die entsprechende Rolle auch die Berechtigung für eine Aktion
bekommen hat, kann sie auch diese durchführen. Das heißt also, dass z.B. die
`manage_commonsbooking_cb_item` Berechtigung zu verleihen wenig Sinn ergibt,
wenn nicht zumindest auch die edit_cb_items Berechtigung oder eine andere
Berechtigung für Artikel verliehen wird.

Hier auch besonders relevant ist die Berechtigung **edit_others_cb_bookings** .
Diese bestimmt, ob ein Manager in der Lage ist Buchungen von anderen Nutzenden
zu stornieren.

###  Andere Rollen einem Artikel / Standort zuweisen {#filterhook-manager-roles}

::: tip Ab Version 2.8.2
:::

Es ist mit einem kleinen Codeschnipsel möglich, auch eine weitere Rolle zu
definieren die einem Artikel / Standort zugewiesen werden kann und diesen dann
entsprechend ihrer / seiner Berechtigung bearbeiten darf. Das funktioniert mit
einem [Filter](../advanced-functionality/hooks-and-filters) (Dort findest du auch
mehr Infos zu Codeschnipseln). Dieser heißt `commonsbooking_manager_roles` und
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

