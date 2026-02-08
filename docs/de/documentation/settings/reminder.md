# E-Mail-Benachrichtigungen

__

Zusätzlich zu den E-Mails die die Nutzenden bei Bestätigung bzw. Stornierung einer Buchung erhalten, kann CommonsBooking auch Erinnerungs-E-Mails an Nutzende und Standorte vor Beginn bzw. nach Beendigung einer Buchung versenden. Diese können inviduell aktiviert und deaktiviert werden. Diese Seite gibt einen Überblick über sämtliche E-Mail Benachrichtigungen die CommonsBooking versendet.

## An Ausleihende

CommonsBooking versendet an Ausleihende folgende Emails, deren Text hier angepasst werden kann: "Einstellungen → CommonsBooking → Vorlagen" bzw. "Einstellungen -> CommonsBooking -> Erinnerung"

  * **Buchungsbestätigung / Stornierungsbestätigung**
    * Standardmäßig aktiviert
    * Vorlage unter "Einstellungen → CommonsBooking → Vorlagen"
    * Kann in Blindkopie auch an weitere E-Mail-Adressen gesendet werden (siehe unten) 
  * **Einschränkungsbenachrichtigung**
    * Wird versendet, wenn "E-Mail senden" in Einschränkung geklickt
    * Vorlage unter "Einstellungen → CommonsBooking → Einschränkungen"
    * Wird standardmäßig in Blindkopie auch an Stations-Emails gesendet (siehe unten)
  * **Buchungserinnerung** .
    * Nicht standardmäßig aktiviert
    * Vorlage unter "Einstellungen → CommonsBooking → Erinnerung"
    * Die Nutzenden können so gefragt werden, ob die Buchung bestehen bleiben soll oder eventuell storniert werden kann.
  * **E-Mail nach Ende der Buchung** :
    * Nicht standardmäßig aktiviert
    * Einen Tag nach der Buchung erhalten Nutzende eine E-Mail, die nach etwaigen Problemen fragt und ggf. z.B. für Spendenaufrufe etc. genutzt werden kann

##  An euer Team

###  Administrator:innen

**WordPress-Seiten-Administrator:innen** (unter WordPress-Einstellungen→Allgemein → Administrator-E-Mail-Adresse) erhalten Buchungs- und Stornierungs-Emails in Blindkopie (BCC)

### Standorte
  * **Standorte** (unter Standort → Standort E-Mail) erhalten **Buchungs- und Stornierungs-Emails** , wenn beim Standort die Option “Kopie der Buchungen/Stornierungen per E-Mail an den Standort senden” aktiviert ist
  * **Standorte** (unter Standort → Standort E-Mail) erhalten **Erinnerungen zu Buchungen** , wenn die Option unter “Einstellungen → “Erinnerungen → Erinnerung für Standorte vor Buchungsbeginn” bzw. "Erinnerung für Standorte vor Buchungsende" eingestellt ist UND in dem entsprechenden Standort die Option "Erinnerungsmail zum Buchungsstart" bzw. "Erinnerungsmail zum Buchungsende" aktiviert ist.

::: warning **Wichtig**
Es reicht nicht, Personen als [Standort-Manager:innen](/dokumentation/grundlagen/rechte-des-commonsbooking-manager) einzutragen! Um E-Mails zu erhalten, muss die Email-Adresse in das Feld “Standort → Standort E-Mail” eingetragen werden.
:::

###  Artikelbetreuende

  * Unter “Artikel → E-Mail-Adresse des Artikelbetreuers” kann konfiguriert werden, dass Artikelbetreuer:innen über Buchungseinschränkungen benachrichtigt werden. Dies kann in einem Workflow genutzt werden, bei dem bei gemeldeten Schäden an einem Artikel durch den Standort eine Einschränkung erstellt wird, die den Artikel automatisch von der Buchung sperrt und den Artikelbetreuenden über das Problem informiert.

