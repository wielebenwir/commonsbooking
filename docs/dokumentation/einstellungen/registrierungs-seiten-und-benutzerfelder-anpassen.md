#  Registrierung & Login anpassen

__

##  Login- und Registrierungsseiten im Website Stil

CommonsBooking nutzt die Nutzerdaten, die in WordPress gespeichert werden. Da
die Anforderungen an die Nutzerregistrierung sehr individuell sind, haben wir
darauf verzichtet, eine eigene Nutzerregistrierung und Login in CommonsBooking
nachzubauen.

Wir empfehlen euch, das in WordPress integrierte Registrierung und Login-
Formular zu verwenden. Wenn ihr weitere Daten wie z.B. Adressdaten,
Telefonnummer erfassen wollt, empfehlen wir euch die Nutzung von weiteren
Plugins, die sich speziell um die Anpassung der Registrierung und Login
kümmern.

Hierfür könnt ihr z.B. das Plugin " [ Theme my Login
](https://wordpress.org/plugins/theme-my-login/) " verwenden, die kostenlose
Version reicht aus um Registrierung und Anmeldungsseiten in eurem Website-Stil
zu zeigen. Bei Problemen mit Spam könnt ihr ein Captach-Plugin installieren.

Um komplett eigene zusätzliche Felder zu erstellen, könnt ihr die Plugins " [
WP Members ](https://wordpress.org/plugins/wp-members/) " oder " [ Ultimate
Member ](https://wordpress.org/plugins/ultimate-member/) " benutzen, diese
bieten auch noch weitere EInstellungen zur Zugangskontrolle, Emails, usw.

Das einzige uns bekannte _kostenlose_ Plugin, das auch die Profil/Kontoseite
anpasst, ist [ UsersWP ](https://wordpress.org/plugins/userswp/) .

##  Tips für die Konfiguration von UsersWP für CommonsBooking-Seiten

####  Registrierungsseitenfelder

Fügt "Datenschutz" und "AGB" hinzu.

![](aea2d81bb65f4f3efcb1dd4c4e44d433.jpg)

Fügt ein Text-Feld " **Adresse** " hinzu, klickt auf "Erweitert anzeigen" und
tragt unter **Feldkennung** " **address** " ein (dies ist insbesondere wichtig
falls ihr CB1 verwendet habt, da der Feldname dort "address" ist).

![](1ad031dd9429633c2b6f0be5bda1cad3.jpg)

Ebenso solltest du das Feld "Telefon" hinzufügen. Nutze dafür nicht das
Telefonfeld, sondern ein einfaches Textfeld (wie bei Adresse), und setze die
**Feldkennung** auf " **phone** ".

####  Profil/Kontoseite aufräumen

UsersWP hat leider einige unnötige Elemente auf der Profil-Seite (etwa:
"Notifications"). Um diese auszublenden könnt ihr [ **diesen Code verwenden**
](https://gist.github.com/flegfleg/8b4fc52dd3f2eed7fc489b55c8137872) . Er muss
entweder in die Datei " **functions.php** " in deinem Theme-Verzeichnis
kopiert werden, _oder_ ihr verwendet das Plugin " [ Code Snippets
](https://wordpress.org/plugins/code-snippets/) ".

####  Mehr Tips

  * Oft verstecken sich wichtige Einstellungen unter dem Knopf “Erweitert anzeigen” rechts oben (Etwa die Email-Templates!) 
  * Weitere Einstellungen 
    * **Profil- > Meta-Tags ** deaktivieren 
    * **Profil - > “Headerbereich auf dem Profil” & “Inhaltsbereich auf dem Profil” ** deaktiveren 
    * **Autoren-Box - > Autorenbox ** deaktivieren 
    * Setzt unter **Registrieren - > Passwort-Höchstlänge ** auf 30 

