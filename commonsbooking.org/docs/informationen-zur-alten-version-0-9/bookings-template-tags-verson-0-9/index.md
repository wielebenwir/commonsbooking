  1. __
  2. [ Home  ](https://commonsbooking.org/)
  3. __
  4. [ Dokumente  ](https://commonsbooking.org/dokumentation/)
  5. __
  6. [ Informationen zur alten V...  ](https://commonsbooking.org/docs/informationen-zur-alten-version-0-9/)
  7. __
  8. Template Tags & Shortcodes (Version 0.9.x) 

#  Template Tags & Shortcodes (Version 0.9.x)

__

#  Bookings Template Tags

Please note: USER_NAME has been depreciated. Please use FIRST_NAME / LAST_NAME

Die folgenden Template-Tags können in Nachrichten und E-Mails benutzt werden.
Usage:

Hello {{FIRST_NAME}}, thanks for booking item {{ITEM_NAME}}.

A full Example: [ Confirmation email example ](https://dein-
lastenrad.de/wiki/Confirmation_email_example) See also [ Registration Mail
Template Tags ](https://dein-
lastenrad.de/wiki/Registration_Mail_Template_Tags)

####  **Date**

{{DATE_START}} – Pickup Date

{{DATE_END}} – Return Date

####  **Item**

{{ITEM_NAME}} – Name of the booked Item

{{ITEM_THUMB}} – Thumbnail of the booked Item

{{ITEM_CONTENT}} – The short description of the item

####  **Location**

{{LOCATION_NAME}} – Name of the location

{{LOCATION_CONTENT}} – The short description of the location

{{LOCATION_ADRESS}} – The adress

{{LOCATION_THUMB}} – Thumbnail of the Location

{{LOCATION_CONTACT}} – Contact information

####  **User**

{{FIRST_NAME}} – First name of user

{{LAST_NAME}} – Last name of user

{{USER_EMAIL}} – User email

{{USER_ADDRESS}} – Address

{{USER_PHONE}} – Phone number

####  **Code**

{{CODE}} – The booking code.

####  **Site**

{{SITE_EMAIL}} – The email address the confirmation email will be sent from.

#  **Registration Mail Template Tags**

The following template tags can be used in the registration confirmation email
settings. Example: [ Registration Email Example ](https://dein-
lastenrad.de/wiki/Registration_Email_Example)

Usage:

Hi {{USER_LOGIN}}, here is your account information

###  **All template tags**

{{USER_LOGIN}} – The chosen user name

{{EMAIL}} – The Email

{{FIRST_NAME}}

{{LAST_NAME}}

{{PHONE}}

{{ADDRESS}}

{{ACTIVATION_URL}} – The url used for activation.

{{PASSWORD}} – The password – Depreciated! See discussion here: [
https://bitbucket.org/wielebenwir/commons-booking/issues/125/password-can-not-
be-sent-in-registration ](https://bitbucket.org/wielebenwir/commons-
booking/issues/125/password-can-not-be-sent-in-registration)

#  **Shortcodes**

Commons Booking bietet 2 shortcodes:

  * **cb_items** (Liste der Artikel) 
  * **cb_item_categories** (Liste der Artikel-Kategorien) – ab Version 0.9 

##  **Nutzung**

###  **Artikel-Liste**

In a WordPress editor field, enter the following to show all items, sorted
alphabetically by title

[cb_items]

###  **Kategorien-Liste**

[cb_item_categories]

##  **Artikel-Liste – Parameter**

The parameters are a subset of the wordpress WP_QUERY params.

###  **p: item id**

Get one single item by item id

[cb_items p=17]

(How to find the item id: Edit the item and look at the URL: "?post= **17**
&action=edit )

###  **cat: category id**

Get all items from a category

[cb_items cat=4]

###  **orderby: set sort order**

title, date, rand, menu_order

[cb_items orderby=title]

###  Navigation

[ ← Login- und Registrierungsseiten anpassen (Version (0.9.x)
](https://commonsbooking.org/docs/informationen-zur-alten-
version-0-9/einstellungen-version-0-9/) [ Widgets & Themes (Version 0.9.x) →
](https://commonsbooking.org/docs/informationen-zur-alten-version-0-9/widgets-
themes-version-0-9/)

