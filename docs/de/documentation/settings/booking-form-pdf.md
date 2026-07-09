# Ausleihformular-PDF

Ab Version 2.12.

Unter Einstellungen -> CommonsBooking findest du im Reiter **Vorlagen** den Abschnitt zum **Ausleihformular-PDF**.

CommonsBooking kann an die Bestätigungsmail einer Buchung ein PDF-Ausleihformular anhängen. Das Formular fasst die wichtigsten Buchungsdaten zusammen (Abhol- und Rückgabetermin, Leihgegenstand, Standort, ausleihende Person) und bietet Platz für Unterschriften, Zubehör-Häkchen sowie kurze Hinweise zu Nutzung und Rückgabe. So haben Standort und ausleihende Person ein ausdruckbares Übergabe- und Belegdokument zur Hand.

::: tip TIPP
Das Ausleihformular wird **nur an bestätigte Buchungen** angehängt – nicht an Stornierungen oder unbestätigte Buchungen.
:::

## Aktivieren

Setze im Reiter **Vorlagen** das Häkchen bei **„Ausleihformular-PDF an bestätigte Buchungsmail anhängen"** und speichere die Einstellungen. Ab dann erhält jede bestätigte Buchung das PDF als Anhang.

## Was im Formular steht (Standard-Vorlage)

Die mitgelieferte Standard-Vorlage ist ein DIN-A4-Formular im Querformat mit zwei Spalten:

- **Ausleihe:** Abholdatum, Rückgabedatum, Leihgegenstand, Standort, Buchungsnummer
- **Ausleihende Person:** Name, Adresse, E-Mail
- **Gebuchtes Zubehör:** Ankreuzfelder
- **Unterschriften:** Ort/Datum und Unterschrift
- **Nach der Rückgabe:** Feld für Schäden und Unterschrift
- **Hinweise zu Nutzung und Rückgabe:** kurze, anpassbare Standardhinweise

Leere Werte – etwa Adressfelder, die CommonsBooking nicht selbst verwaltet – werden im PDF als ausfüllbare Linien dargestellt.

::: warning ACHTUNG
Die enthaltenen Hinweistexte sind eine **Vorlage und keine Rechtsberatung**. Passe sie vor dem Einsatz an eure lokalen Nutzungsbedingungen an.
:::

## Vorlage anpassen

Im Feld **„Template für das Ausleihformular-PDF"** kannst du das Layout frei bearbeiten. Es gelten dieselben [Template-Tags](../administration/template-tags) wie in den Buchungsmails, zum Beispiel:

```
{{booking:pickupDatetime}}
{{booking:returnDatetime}}
{{item:post_title}}
{{location:post_title}}
{{user:first_name}} {{user:last_name}}
```

Du kannst HTML und CSS verwenden (inklusive eines `<style>`-Blocks). Wichtige Hinweise:

- **Seitenformat und Ausrichtung** legst du über eine CSS-`@page`-Regel fest, z. B. `@page { size: A4 landscape; }`. Die Standard-Vorlage nutzt Querformat; ohne eigene `@page`-Angabe wird Hochformat verwendet.
- **Logo:** Die Standard-Vorlage bindet automatisch das Logo deiner Website ein (Customizer -> „Logo"). Ist keins gesetzt, wird das CommonsBooking-Logo verwendet.
- **Bilder** werden aus Sicherheitsgründen nur von der eigenen Website geladen.

::: tip TIPP
Die Beschriftungen der mitgelieferten Standard-Vorlage nutzen die WordPress-Übersetzungen von CommonsBooking.
:::

## Vorschau

Im Abschnitt **„Vorschau des Ausleihformular-PDFs"** kannst du das Ergebnis prüfen, ohne eine Mail zu verschicken:

- Klicke auf **„Ausleihformular-PDF ansehen"** – das PDF der neuesten bestätigten Buchung öffnet sich in einem neuen Tab.
- Optional gibst du eine **Buchungs-ID** ein, um genau diese (bestätigte) Buchung als Vorschau zu rendern. Bleibt das Feld leer, wird die neueste bestätigte Buchung verwendet.

Die Vorschau steht erst zur Verfügung, wenn eine Vorlage gespeichert ist und mindestens eine bestätigte Buchung existiert.

## Auf Standard zurücksetzen

Mit **„Auf Standard-Template zurücksetzen"** ersetzt du den Inhalt des Vorlagenfelds durch die mitgelieferte Standard-Vorlage. **Speichere anschließend**, damit die Änderung erhalten bleibt.

## Wenn etwas nicht klappt

- **Beim Speichern:** Aktivierst du den Anhang, obwohl keine Vorlage gespeichert ist – oder eine benötigte PHP-Erweiterung fehlt –, erscheint direkt ein Hinweis im Backend.
- **Beim Versand:** Lässt sich das PDF nicht erzeugen, wird die Bestätigungsmail trotzdem verschickt (nur ohne Anhang), und im Backend erscheint ein Hinweis mit dem Grund. Prüfe in diesem Fall deine Vorlage.
