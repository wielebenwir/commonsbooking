  1. __
  2. [ Home  ](https://commonsbooking.org/)
  3. __
  4. [ Dokumente  ](https://commonsbooking.org/dokumentation/)
  5. __
  6. [ Schnittstellen / API  ](https://commonsbooking.org/docs/schnittstellen-api/)
  7. __
  8. Die CommonsBooking API nutzen 

#  Die CommonsBooking API nutzen

__

CommonsBooking verfügt über eine eigene API [ (Was ist die CommonsBooking
API), ](https://commonsbooking.org/docs/schnittstellen-api/was-ist-die-
commonsapi/) über die ihr bequem auf entsprechende Daten zugreifen könnt oder
die Daten für andere Plattformen oder Dienste zur Verfügung stellen könnt.

Die API basiert auf der in [ WordPress implementierten REST-API
](https://developer.wordpress.org/rest-api/)

##  **Aktivierung der API**

  * Die API ist standardmäßig deaktiviert und es gibt keine Verbindungen zu externen Services oder Plattformen.. Unter _Einstellungen_ -> _CommonsBooking_ -> Tab: _API / Export_ kann die API über die entsprechende Checkbox aktiviert werden. 

##  API-Einstellungen allgemein

  * API aktivieren: Aktiviert generell den API-Zugriff 
  * API Zugang ohne API-Schlüssel: Wenn diese Option aktiviert ist, kann auf die API auch ohne einen individuellen API-Key zugegriffen werden. Diese Einstellung ist sinnvoll, wenn ihre eure Daten mit mehreren Plattformen teilen möchtet. 
  * API-Freigaben: Die Schnittstelle kann für verschiedene Endpunkte bzw. anfragende Seiten freigegeben werden. Um die Zugriffsrechte entsprechend zu steuern könnt ihr auch mehrere unterschiedliche Freigaben anlegen. 

##  API-Einstellungen pro API-Freigabe

  * Schnittstellen-Name: Ein beliebiger Name für eure interne Bezeichnung der API 
  * Schnittstelle aktiviert: Die API-Freigabe ist erst aktiviert, wenn diese Häkchen gesetzt ist. 
  * Push-URL: Hier könnt ihr die URL des empfangenden Systems eingeben. CommonsBooking wird diese URL bei jeder Änderung der Daten aufrufen, sodass das entfernte System dann über eine Änderung der Daten informiert und diese über einen separaten API-Call abrufen kann. So kann ein Datenaustausch in Echtzeit ermöglicht werden. 
  * API-Schlüssel: Hier könnt ihr einen selbstgewählten API-Schlüssel eingeben. Wenn der Schlüssel gesetzt ist, muss das abfragende System in jeder Abfrage den Parameter apikey= _[Schlüssel]_ mitliefern. 

##  Es stehen folgende API-Routen zur Verfügung:

**Verfügbarkeit**

  * Beschreibung: Zeit die Verfügbarkeiten von Artikel an Standorten an 
  * Route: /wp-json/commonsbooking/v1/availability 
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.availability.schema.json 

**Items / Artikel**

  * Beschreibung: Gibt Daten zu allen veröffentlichen Artikeln inklusive der damit verknüpften Verfügbarkeiten und Standorte zurück 
  * Route: /wp-json/commonsbooking/v1/items 
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.items.schema.json 

**Locations / Standorte**

  * Beschreibung: Gibt eine Liste aller Standorte zurück inklusive der Geo-Koordinaten 
  * Route: wp-json/commonsbooking/v1/locations 
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.locations.schema.json 

**Projekte / Projects**

  * Beschreibung: Gibt die Basisdaten der WordPress-Instanz zurück 
  * Route: wp-json/commonsbooking/v1/projects 
  * Schema: https://github.com/wielebenwir/commons-api/blob/master/commons-api.projects.schema.json 

###  Navigation

[ ← Was ist die CommonsAPI? ](https://commonsbooking.org/docs/schnittstellen-
api/was-ist-die-commonsapi/)

