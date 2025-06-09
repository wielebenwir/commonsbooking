#  Buchungen anlegen & Adminbuchung

__

Buchungen sollten in der Regel nur über den entsprechenden Kalender eines
Artikels (also im Frontend) angelegt und storniert werden.

##  Buchung als Admin anlegen / Adminbuchung

Nutzende mit Admin-Berechtigungen können Buchungen auch über das Backend
(CommonsBooking Admin-Oberfläche) anlegen. Dabei ist jedoch folgendes zu
beachten:

  *     * Es findet keine Prüfung der so erstellten Buchungen auf Plausibilität statt. Fehlerhafte Einstellungen können zu Problemen bei der Darstellung im Kalender führen. 
    * Der Status der Buchung muss manuell über die Statusauswahl (Kasten rechts oben) auf confirmed gesetzt werden. 
    * Hinweis: Buchungen mit dem Status “unconfirmed” werden automatisch über einen WordPress Cronjob nach ca. 10 Minuten gelöscht. Dies gilt auch für Admin-Buchungen. 
    * Die Buchung kann auch für einen anderen Nutzenden angelegt werden. Dazu bitte den Login-Namen im Feld “Buchende Person” auswählen. Die Buchung erscheint dann auch in der Buchungsliste des Nutzenden. 
    * Wird die Buchunge auf “confirmed” gesetzt und der Eintrag dann gespeichert wird eine normale Buchungsbestätigung an die unter “Buchende Person” eingegeben E-Mail-Adresse versendet. 

