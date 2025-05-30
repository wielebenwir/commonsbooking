#  Vorlagen

__

Unter Einstellungen -> CommonsBooking findest du den Reiter **Vorlagen**

##  E-Mail Vorlagen

In den Vorlagen kannst du die Inhalte der Buchungsmails und die Absender-
Adresse der Buchungs-Mails festlegen.

**Tipp:** Möchtest du den Standard wieder herstellen, so lösche einfach alle
Einträge und speicher die Seite, die Standard-Vorlagen werden dann wieder
geladen.

Um Daten aus der Buchung (wie Artikel, Buchungszeitraum etc.) in die Mail zu
integrieren, verwendet CommosBooking so genannte TemplateTags. Dies sind
Platzhalter, die in der E-Mail dann durch die entsprechenden Daten ersetzt
werden.

In den Standard-Vorlagen sind bereits die wichtigsten TemplateTags enthalten.
Du kannst sie an jeder beliebige Stelle in den Vorlagen verwenden.

Du kannst weitere Template-Tags verwenden, wenn dir die standardmäßig
enthaltenen nicht ausreichen.

Eine Übersicht zur Verwendung der [Template-Tags findest du hier](/docs/einstellungen/template-tags/)

##  iCalendar Dateien

Ab 2.8

CommonsBooking ist in der Lage aus den getätigten Buchungen eine .ics Datei zu
generieren, die mit den meisten digitalen Kalendern kompatibel ist. Du kannst
hier, genau wie in den E-Mail Vorlagen, die entsprechenden Template Tags
verwenden. Die resultierende Kalenderdatei wird an die E-Mail angehängt und
die Nutzenden können sie in ihren digitalen Kalender importieren. Die meisten
E-Mail Programme unterstützen diesen Import mit einem Klick. Aktuell löscht
die Stornierung einer Buchung noch nicht den erzeugten Kalendereintrag.
Darüber hinaus kannst du auch einen abonnierbaren Kalender erstellen, mehr dazu : [iCalendar Feed](/docs/buchungen-verwalten/icalendar-feed/) .

##  Template und Buchungsprozess-Vorlagen

In diesem Abschnitt findest du verschiedene Textbausteine, die an
unterschiedlichen Stellen ausgegeben werden. Die Felder enthalten jeweils eine
Beschreibung über die Verwendung der Textbausteine.

###  Benutzer\*innen-Details auf der Buchungsseite

In diesem Abschnitt definierst du, welche Benutzer\*innen Daten in der
Buchungsdetailansicht angezeigt werden. Hier ist es z.B. möglich, Adressdaten
(Straße), Telefonnummer hinzuzufügen. Da jede CommonsBooking-Installation die
Verwaltung der Nutzendendaten selbst organisiert, können wir hier keine
vordefinierten Felder anbieten. Bitte prüft deshalb, wie die Feldnamen in
eurer Nutzer_ innen-Verwaltung heißen und fügt diese dann entsprechend hinzu.
In der Vorlage könnt ihr auch einfache HTML-Formatierungen z.B. für
Zeilenumbrüche (`<br>`) verwenden.
Beispiel, um das Feld "phone" und das Feld "address" aus den Userdaten
anzuzeigen:
```
{{[Telefon: ]user: _phone_ }} <br>
{{[Adresse: ]user: _address_ }}
```

In den eckigen Klammern steht die Bezeichnung, die vor dem jeweiligen Wert
angezeigt werden soll.

##  Bildformatierung

Wenn du die Shortcodes [cb_items] oder [cb_locations] nutzt, erzeugt
CommonsBooking entsprechende Listenansichten mit Vorschaubildern der Artikel
und Standorte. In dieser Einstellung kannst du die Standardgröße dieser
Vorschaubilder anpassen.

##  Farben

Sämtliche Farben in der Benutzeroberfläche sind anpassbar. Um Farben wieder
auf die Standardwerte zurückzusetzen kannst du in der entsprechenden Farbe auf
den "Leeren" Knopf drücken und anschließend deine Änderungen speichern. Jetzt
sollte für das entsprechende Feld wieder der Standardwert eingestellt sein.

