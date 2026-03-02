#  Update-Informationen

Detaillierte Release-Informationen findest du auf der [CommonsBooking WordPress-Seite](https://de.wordpress.org/plugins/commonsbooking/#developers)

##  Hinweise zum Update auf Version > 2.6

Dieses Update bringt einige neue Funktionen und Verbesserungen. Hier die
wichtigsten Änderungen im Überblick. Nutzt gerne auch die Doku, um euch über
die neuen Funktionen zu informieren.

**Wichtiger Hinweis:**
Wenn ihr buchbare Zeitrahmen definiert hattet, die folgendermaßen eingestellt
sind  
_– Ganztägig = aktiviert  
– Start-Datum ist gesetzt  
– Wiederholung: Keine Wiederholung  
– Kein End-Datum _  
dann wurden diese bisher als ganztägige Buchungszeitfenster ohne Enddatum im
Kalender dargestellt.  
Nach dem Update wird bei dieser Einstellung **nur noch das Start-Datum als
buchbar angezeigt** .  
Um wieder alle Tage als buchbar auszugeben, müsst ihr bei **Wiederholung =
täglich** auswählen.  
Diese Änderung basiert darauf, dass wir die Logik angepasst haben und die
vorherige Darstellung grundsätzlich zwar funktioniert hat aber nicht stringent
war.

**Template-Änderungen**

  * Es wurden im Zuge der Erweiterungen auch fast alle Template-Dateien geändert. Wenn ihr Dateien im Verzeichnis /templates geändert habt, empfehlen wir diese Vorab zu sichern und nach dem Update zu prüfen, welche Anpassungen ihr ggf. in die neuen Templates integrieren möchtet. 

**Neue Funktionen**

  * **Buchungen als eigener Menüpunkt,** bessere Übersicht im Backend. Die Buchungen sind nicht wie bisher unter Zeitrahmen zu finden, sondern nun in einem neuen eigenen Menüpunkt “Buchungen” aufgelistet. 
  * **Dashboard:** Überarbeitung des Dashboards. Zeigt nun heutige Abholungen und Rückgaben an. 
  * Erinnerungsmails: Ausleihende erhalten vor und nach einer Buchung entsprechende Erinnerungs und Feedback-E-Mails. 
  * **Nutzungseinschränkungen** verwalten: Es können nun Einschränkungen verwaltet werden. Dies können Hinweise auf defekte oder fehlende Teile sein oder das Anlegen eines Totalausfalls (z.B. wegen einer Reparatur). Buchungen die innerhalb des betroffenen Zeitrahmens liegen, werden bei einem Totalausfall automatisch storniert und eine Info-E-Mail an Nutzende und CB-Manager versendet. Hinweise werden im Buchungskalender angezeigt und Nutzende können über Änderungen benachrichtigt werden. 
  * Eine **Kartenansicht** kann jetzt für die Standort Seite eingestellt werden. Die Einstellung ist am Standort aktivierbar. 
  * **Anpassbarer Buchungsbestätigungs-Text** auf der Buchungsseite (“Deine Buchung wurde bestätigt”). Kann nun in Einstellungen -> Vorlagen angepasst werden. 
  * **Maximaler Vorausbuchungszeitraum** ist nun definierbar. Standardmäßig ist dieser im Standard auf 365 Tage eingestellt. Diese Einstellung gilt auch für alle existierenden Zeitrahmen. Die Einstellung erfolgt über die Zeitrahmen. Der Zeitrahmen kann so für einen längeren oder unendlichen Zeitraum angelegt werden. Nutzende können dann vom heutigen Tag gerechnet immer nur maximal x Tage im Voraus buchen. 
  * **Thumbnail-Größe** in Artikel- und Standort-Listen nun einstellbar (Einstellungen -> Vorlagen -> Bildformatierung). 
  * **GBFS-API** integriert, um mit anderen Mobilitäts-Plattformen einen standardisierten Datenaustausch zu ermöglichen. 
  * **Kalender-Legende:** Der Buchungskalender hat nun eine Legende erhalten, um die Farben und Einstellungen des Kalenders anzuzeigen. 
  * **Für Experten:**   
Metadatensets (hier können individuelle Attribute / Felder zu Artikeln oder
Kategorien hinzugefügt werden).  
API erweitert (einzelne API-Freigaben möglich).

**Erweitert und angepasst**

  * Die Kartenansicht zeigt in dem kleinen Vorschau-Popup nicht mehr Abholhinweise und Kontaktdaten an, da wir diese erst im Buchungsprozess ausgeben wollen. Zudem wurde diese Optionen aus den Einstellungen für die Karte entfernt. 
  * In der Export-Funktion werden die von CommonsBooking erstellten benutzerdefinierten Felder angezeigt, um sie dem Export hinzufügen zu können. 
  * Die Buchungsliste wurde überarbeitet. Das Design wurde entsprechend angepasst und der Buchungsstatus integriert. 
  * Exportfunktion um weitere Standardfelder erweitert (Name des Ausleihenden etc.) 
  * Im Buchungskalender kann nun die Zeitauswahl im Kalender zurückgesetzt werden. 
  * Buchungscodes werden nun auch in der Buchungsliste (Meine Buchungen) angezeigt. 
  * Abholhinweise werden im Buchungskalender und bei der Buchungsbestätigung nun anders dargestellt. Achtung: Template-Änderung. Bei manueller Template-Änderung die Anpassungen bitte prüfen und ggf. korrigieren. 
  * Bei Stornierungen wird die Stornierungs-Zeit gespeichert und in der Buchungsdetailansicht oben in der Statusmeldung angezeigt. 

**Behobene Fehler**

  * Standorte in der Zeitrahmenbearbeitung sind nun alphabetisch geordnet 
  * Bei fehlerhaft konfigurierten Servern konnte es zu einem Fehler im Zusammenhang mit der Geo-Kodierung kommen, der das Speichern von Standorten verhinderte. Durch Umstellung auf eine andere Software-Bibliothek sollte dieser Fehler behoben sein. 
  * Kleinere Anpassungen um Kompatibilität mit WordPress 5.9 und PHP 8 zu garantieren. 

Alle Changelog-Infos auch unter: https://de.wordpress.org/plugins/commonsbooking/#developers

