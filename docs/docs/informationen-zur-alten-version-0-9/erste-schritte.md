#  Erste Schritte (Version 0.9…)

__

###  **Plugin-Seiten in das Menü stellen**

Commons Booking erzeugt bei einer Aktivierung folgende Seiten:

  * **Confirm booking** / **Buchung bestätigen** –Buchung wird zur Überprüfung angezeigt, per Klick bestätigt. 
  * **Booking** / **Buchung** – Details einer Bestätigten Buchung. Auf dieser Seite kann die Buchung storniert werden. 
  * **Items** / **Artikel** – Zeigt die Liste der verfügbaren Artikel. _Diese Seite muss ins WordPress-Menü_ . 
  * **My Bookings** / **Meine Buchungen** – Zeigt die Liste der Buchungen. _Diese Seite muss ins WordPress-Menü_

Gegebenenfalls müssen diese Seiten manuell erstellt werden (Titel und Slug/URL
spielen dabei keine Rolle) und dann in den Commons Booking Einstellungen (/wp-
admin/options-general.php?page=commons-booking) den jeweiligen Funktionen
zugeordnet werden.

###  **Registrierung ermöglichen (optional)**

Nur angemeldete WordPress-Benutzer können Artikel buchen. Soll die Ausleihe
für die Allgemeinheit ermöglicht werden, muss in den WordPress Einstellungen
Jeder kann sich registrieren aktiviert sein.

###  **Buchungs-Kommentare ermöglichen (optional)**

Commons Booking erlaubt Benutzern, Kommentare zu Hinterlassen, die im
Kalender-Popup gezeigt werden. Dazu muss unter

_Einstellungen - > Diskussion _ das Feld Erlaube Besuchern, neue Beiträge zu
kommentieren aktiviert sein. Bereits erstellte Beiträge (vor der Aktivierung
des Schalters) müssen noch einmal individuell aktiviert werden unter _Artikel
- > Bearbeiten _ Kommentare erlauben.

###  **Artikel, Standorte und Zeiträume anlegen**

Damit Artikel zur Entleihe zur Verfügung stehen, müssen mindestens ein
Artikel, Standort und Buchungszeitraum definiert sein.

  1. Standort anlegen 
  2. Artikel anlegen 
  3. Zeitraum anlegen und Artikel und Standort zuweisen. 

