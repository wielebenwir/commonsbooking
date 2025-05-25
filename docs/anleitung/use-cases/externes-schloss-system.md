# Anbindung eines Externen Schloss-Systems

::: info
Falls du eine spezielle Anfrage wie diese Hast, komm in den Signal oder Slack Chat.
:::

Da das CommonsBooking Plugin die [Wordpress-Postings]() erweitert, kannst du über [Meta-Felder]() eines
[Buchungs]()-Posts zusätzliche Informationen zu einer Buchung hinterlegen. Standardmäßig liegen dort Start- und Endzeitpunkt der Buchung.

Beispielsweise möchtest du aber ein externes Schloss-System anbinden und dieses direkt mithilfe der 
[Buchungs-Codes]() des Plugins, in den Schließ-Prozess deines Verleihs integrieren.
Dazu legst du die Meta-Felder über das Wordpress-Admin-Backend an und kannst diese dann über einen 
Aufruf der Wordpress-REST-API in dein internetfähges Schloss integrieren.

Dieses Video beschreibt die einzelnen Schritte des Prozess:

* Dazu in den Einstellungen -> Tab “Erweitert” auswählen 
* im Feld Meta-Daten die gewünschten Felder nach der dort benannten Syntax anlegen. Die Erläuterung zur Syntax findet ihr in der Feldbschreibung. z.B. `item;ItemKeyCode;Schloss-Code;text;Code` für das Zahlenschloss
* Dieses Metafeld könnt ihr nun über die oben genannten Shortcodes in den E-Mail-Vorlagen einsetzen.
  Beispiel: `[Der Code für das Zahlenschloss lautet: ]item:ItemKeyCode`.
* Der Text in den eckigen Klammern `[ ]` dient als Beschreibungstext, der vor dem eigentlichen Metafeld ausgegeben wird. Der Vorteil hier ist, dass der Beschreibungstext inklusive des Werts nur ausgegeben wird, wenn das dynamische Feld einen Wert enthält. In diesem Beschreibungstext sind auch einfache HTML-Codes erlaubt (z.B. br, strong, etc.)

::: raw
<iframe width="560" height="315" src="https://www.youtube.com/embed/f4rr77GpB9o?si=EOGdI7yfinthNXyL" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
:::
