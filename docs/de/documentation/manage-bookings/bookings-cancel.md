#  Buchung stornieren


Buchungen sollten in der aktuellen Version nur über die Frontend-Ansicht (also
quasi über die Webseite, nicht über die WordPress-Verwaltung) storniert
werden.

Administratoren haben Zugriff auf alle Buchungen. Wenn Administratoren oder
Standort-Manager (Rolle cb_manager) eine Buchung für einen Nutzenden
stornieren möchten, bitte folgendermaßen vorgehen.

  * in WordPress-Verwaltung (Backend) unter Commons-Booking auf “Zeitrahmen” gehen 
  * Dann über die Filter oben nach der Buchung suchen, z.B. mit folgenden Filter: 
    * Typ = buchung 
    * Artikel = (Auswahl des Artikels) 
    * Wiederholungsstart = hier den ersten Buchungstag eingeben 
  * In der Ergebnisliste dann auf die Buchung klicken, um im nachfolgenden Editor-Fenster rechts auf “Vorschau” klicken 
  * Die Buchung öffnet sich dann so, wie sie auch der Nutzende sehen würde. 
  * Hier kannst du nun auf stornieren klicken, um die Buchung zu stornieren. 
  * Dabei wird auch eine E-Mail an den Ausleihenden versendet. 

