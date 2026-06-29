# Booking form PDF

From version 2.12.

Under Settings -> CommonsBooking you will find the **Booking form PDF** section in the **Templates** tab.

CommonsBooking can attach a PDF booking form to the confirmation email of a booking. The form summarizes the most important booking data (pickup and return date, rental item, location, borrower) and provides space for signatures, accessory checkboxes and short notes on use and return. This way, both the location and the borrower have a printable handover and receipt document at hand.

::: tip
The booking form is only attached to **confirmed bookings** – not to cancellations or unconfirmed bookings.
:::

## Activation

In the **Templates** tab, tick the box **"Attach booking form PDF to confirmed booking email"** and save the settings. From then on, every confirmed booking receives the PDF as an attachment.

## What the form contains (default template)

The bundled default template is a two-column A4 form in landscape orientation:

- **Loan:** pickup date, return date, rental item, location, booking number
- **Borrower:** name, address, email
- **Booked accessories:** checkboxes
- **Signatures:** place/date and signature
- **After return:** field for damages and signature
- **Terms and return notes:** short, customizable default notes

Empty values – such as address fields that CommonsBooking does not manage itself – are rendered as fillable lines in the PDF.

::: warning
The included notes are a **template and not legal advice**. Adapt them to your local terms of use before using the form.
:::

## Customizing the template

In the **"Booking form PDF template"** field you can freely edit the layout. The same [template tags](../administration/template-tags) as in the booking emails apply, for example:

```
{{booking:pickupDatetime}}
{{booking:returnDatetime}}
{{item:post_title}}
{{location:post_title}}
{{user:first_name}} {{user:last_name}}
```

You can use HTML and CSS (including a `<style>` block). Important notes:

- **Page size and orientation** are set via a CSS `@page` rule, e.g. `@page { size: A4 landscape; }`. The default template uses landscape; without your own `@page` rule, portrait is used.
- **Logo:** The default template automatically embeds your website's logo (Customizer -> "Logo"). If none is set, the CommonsBooking logo is used.
- **Images** are loaded only from your own website for security reasons.

::: tip
The labels of the bundled default template are automatically translated into the current language of the website (currently German and English).
:::

## Preview

In the **"Booking form PDF preview"** section you can check the result without sending an email:

- Click **"Preview booking form PDF"** – the PDF of the latest confirmed booking opens in a new tab.
- Optionally, enter a **booking ID** to render exactly that (confirmed) booking as a preview. If you leave the field empty, the latest confirmed booking is used.

The preview is only available once a template has been saved and at least one confirmed booking exists.

## Reset to default

With **"Reset to default template"** you replace the content of the template field with the bundled default template. **Save afterwards** so that the change is kept.

## When something goes wrong

- **On save:** If you enable the attachment although no template has been saved – or a required PHP extension is missing – a notice appears directly in the backend.
- **On send:** If the PDF cannot be generated, the confirmation email is still sent (just without the attachment), and a notice with the reason appears in the backend. In this case, check your template.
