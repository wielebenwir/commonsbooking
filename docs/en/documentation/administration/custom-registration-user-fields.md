# Customizing registration & login

## Login and registration pages in your website style

CommonsBooking uses the user data stored in WordPress. Since the requirements for user registration are very individual, we have decided not to rebuild our own user registration and login in CommonsBooking.

We recommend using the registration and login forms integrated into WordPress. If you want to collect additional data such as address details or phone numbers, we recommend using additional plugins that specialize in customizing registration and login.

For this, you can use, for example, the plugin [Theme my Login](https://wordpress.org/plugins/theme-my-login). The free version is sufficient to display registration and login pages in your website style. If you have problems with spam, you can install a captcha plugin.

To create completely custom additional fields, you can use the plugins [WP Members](https://wordpress.org/plugins/wp-members) or [Ultimate Member](https://wordpress.org/plugins/ultimate-member), which also offer additional settings for access control, emails, etc.

The only _free_ plugin we know of that also customizes the profile/account page is [UsersWP](https://wordpress.org/plugins/userswp).

##  Tips for configuring UsersWP for CommonsBooking sites

#### Registration page fields

Add "Privacy" and "Terms of Service".

![](/img/UsersWP-add-fields.jpg)

Add a text field "**Address**", click on **"Show Advanced"**, and enter `address` under **Field Key** (this is particularly important if you have used CB1, since the field name there is "address").

![](/img/UsersWP-adress-field.jpg)

You should also add the "Phone" field. Do not use the phone field for this, but rather a simple text field (like for address), and set the **Field Key** to `phone`.

#### Cleaning up the profile/account page

UsersWP unfortunately has some unnecessary elements on the profile page (such as "Notifications"). To hide these, you can [**use this code**](https://gist.github.com/flegfleg/8b4fc52dd3f2eed7fc489b55c8137872). It must either be copied into the `functions.php` file in your theme directory, _or_ you can use the plugin [Code Snippets](https://wordpress.org/plugins/code-snippets).

#### More tips

  * Important settings are often hidden under the "Show Advanced" button at the top right (such as the email templates!)
  * Additional settings
    * Disable **Profile -> Meta Tags**
    * Disable **Profile -> "Profile Header" & "Profile Content"**
    * Disable **Author Box -> Author Box**
    * Set **Register -> Maximum Password Length** to 30
