#  Frontend-Einbindung (Version 0.9.x)

##  Bookings Template Tags

::: warning
Please note: USER_NAME has been deprecated. Please use FIRST_NAME / LAST_NAME
:::

Die folgenden Template-Tags können in Nachrichten und E-Mails benutzt werden.
Usage:

Hello <span v-pre>{{FIRST_NAME}}</span>, thanks for booking item <span v-pre>{{ITEM_NAME}}</span>.

A full Example: [Confirmation email example](https://dein-lastenrad.de/wiki/Confirmation_email_example) See also [ Registration Mail Template Tags ](https://dein-lastenrad.de/wiki/Registration_Mail_Template_Tags)

####  **Date**

<span v-pre>{{DATE_START}}</span> – Pickup Date

<span v-pre>{{DATE_END}}</span> – Return Date

####  **Item**

<span v-pre>{{ITEM_NAME}}</span> – Name of the booked Item

<span v-pre>{{ITEM_THUMB}}</span> – Thumbnail of the booked Item

<span v-pre>{{ITEM_CONTENT}}</span> – The short description of the item

####  **Location**

<span v-pre>{{LOCATION_NAME}}</span> – Name of the location

<span v-pre>{{LOCATION_CONTENT}}</span> – The short description of the location

<span v-pre>{{LOCATION_ADRESS}}</span> – The adress

<span v-pre>{{LOCATION_THUMB}}</span> – Thumbnail of the Location

<span v-pre>{{LOCATION_CONTACT}}</span> – Contact information

####  **User**

<span v-pre>{{FIRST_NAME}}</span> – First name of user

<span v-pre>{{LAST_NAME}}</span> – Last name of user

<span v-pre>{{USER_EMAIL}}</span> – User email

<span v-pre>{{USER_ADDRESS}}</span> – Address

<span v-pre>{{USER_PHONE}}</span> – Phone number

####  **Code**

<span v-pre>{{CODE}}</span> – The booking code.

####  **Site**

<span v-pre>{{SITE_EMAIL}}</span> – The email address the confirmation email will be sent from.

##  **Registration Mail Template Tags**

The following template tags can be used in the registration confirmation email
settings. Example: [ Registration Email Example ](https://dein-lastenrad.de/wiki/Registration_Email_Example)

Usage:

Hi <span v-pre>{{USER_LOGIN}}</span>, here is your account information

###  **All template tags**

<span v-pre>{{USER_LOGIN}}</span> – The chosen user name

<span v-pre>{{EMAIL}}</span> – The Email

<span v-pre>{{FIRST_NAME}}</span>

<span v-pre>{{LAST_NAME}}</span>

<span v-pre>{{PHONE}}</span>

<span v-pre>{{ADDRESS}}</span>

<span v-pre>{{ACTIVATION_URL}}</span> – The url used for activation.

<span v-pre>{{PASSWORD}}</span> – The password – Deprecated! See discussion here: [https://bitbucket.org/wielebenwir/commons-booking/issues/125/password-can-not-be-sent-in-registration ](https://bitbucket.org/wielebenwir/commons-booking/issues/125/password-can-not-be-sent-in-registration)

##  **Shortcodes**

Commons Booking bietet 2 shortcodes:

  * **cb_items** (Liste der Artikel)
  * **cb_item_categories** (Liste der Artikel-Kategorien) – ab Version 0.9

###  **Nutzung**

####  **Artikel-Liste**

In a WordPress editor field, enter the following to show all items, sorted
alphabetically by title

`[cb_items]`

####  **Kategorien-Liste**

`[cb_item_categories]`

###  **Artikel-Liste – Parameter**

The parameters are a subset of the wordpress WP_QUERY params.

####  **p: item id**

Get one single item by item id

`[cb_items p=17]`

(How to find the item id: Edit the item and look at the URL: "?post= **17**
&action=edit )

####  **cat: category id**

Get all items from a category

`[cb_items cat=4]`

####  **orderby: set sort order**

title, date, rand, menu_order

`[cb_items orderby=title]`

