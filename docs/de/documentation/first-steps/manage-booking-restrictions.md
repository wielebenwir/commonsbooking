#  Buchungseinschränkungen verwalten

__

**Was sind Buchungseinschränkungen?**

Euer Artikel hat einen Defekt oder ist komplett ausgefallen? Mit dem
Buchungseinschränkungen könnt ihr genau solche Fälle verwalten, ohne dass ihr
dazu die Buchungszeitrahmen anpassen müsst. Eure Nutzenden werden über die
Defekte oder den Ausfall informiert und eventuelle Buchungen in dem
betroffenen Zeitraum können automatisch storniert werden und neue Buchungen blockiert werden.

**So funktioniert es:**

  * Klicke im CommonsBooking Menü auf “Buchungseinschränkungen”
  * Dann auf “Neue Einschränkung hinzufügen”

Folgende Einstellungen stehen nun zur Verfügung:

  * **Typ**
    * **Totalausfall:**. Bei einem Totalaufall ist der Artikel nicht mehr buchbar. Sämtliche Buchungen in diesem Zeitraum werden storniert, außer in den [Einstellungen für die Buchungseinschränkungen](/dokumentation/einstellungen-2/einschraenkungen) ist dieses Verhalten explizit deaktiviert worden.
    * **Hinweis:** Ein Hinweis wird lediglich auf der Artikelseite angezeigt und Buchende können auf Wunsch benachrichtigt werden.
  * **Standort**
    * Wähle den betreffenden Standort aus.
    * Wenn du “Alle” wählst, gilt diese Einschränkung für alle Standorte. (Derzeit deaktiviert)
  * **Artikel**
    * Wähle den betreffenden Artikel aus
    * Wenn du “Alle” wählst, gilt die Einschränkung automatisch für alle Artikel an dem oben eingestellten Standort. Dies bietet sich z.B. an, wenn der Standort aufgrund von Krankheit etc. vorübergehend und kurzfristig geschlossen werden muss. Oder wenn sich z.B. die Öffnungszeiten abweichend ändern. Es werden dann alle Buchungen aller Artikel, die mit diesem Standort verknüpft sind, storniert bzw. die Nutzenden benachrichtigt. (Derzeit deaktiviert)
  * **Start und Enddatum**
    * Wähle Start- und das voraussichtliche End-Datum der Einschränkung.
  * **Status**
    * **Nicht aktiv:** Die Einschränkung greift noch nicht. Dies ist für den Fall, dass du die Einschränkungen erst einmal anlegen und speichern möchtest, aber noch nicht die Nutzenden informieren möchtest.
    * **Aktiv:** = Die Einschränkung wird auf den Buchungsseiten angezeigt (im Buchungskalender) und bei einem Totalausfall werden die zwischen Start- und End-Datum liegenden Tage geblockt.
    * **Problem gelöst:** Wenn du diesen Status wählst und anschließend auf “Aktualisieren” klickst wird die Einschränkung aufgehoben. Stornierte Buchungen werden jedoch nicht wiederhergestellt.
  * **Benachrichtigung senden**
    * Bei Klick auf diesen Button sendest Du eine Info-E-Mail an alle Nutzenden. Je nach Typ (Totalausfall oder Hinweis) wird eine unterschiedliche Nachricht versendet.
    * Wenn du den Status auf “Problem gelöst” gesetzt hast und anschließend den Senden-Button klickst, wird eine Infomail mit einem entsprechenden Hinweis gesendet.
      :::warning ACHTUNG!
        Bei einem Totalausfall führt das Setzen einer Einschränkung auf "Problem gelöst" **nicht** dazu, dass die Nutzenden, deren Buchungen storniert wurden, über die Lösung des Problems informiert werden.
        Wir gehen davon aus, dass die Nutzenden sich zu dem Zeitpunkt wahrscheinlich bereits um eine Ersatzbuchung bemüht haben.
        Falls jedoch Stornierungen deaktiviert sind, werden die Nutzenden über die Lösung des Totalausfalls informiert.
        :::
      * Die Vorlagen für diese E-Mails kannst du unter [ Einstellungen -> Commonsbooking -> Einschränkungen ](/dokumentation/einstellungen-2/einschraenkungen) konfigurieren.

