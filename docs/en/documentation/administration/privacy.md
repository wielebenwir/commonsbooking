# Privacy

::: tip Work in progress 2026-01-15
Status: WIP
:::

This page will give an overview of the third-party services used by CommonsBooking. This is to ensure that they can be taken into account in privacy policies for users and are transparent for site operators.

## Third Party Services

### Nominatim

This plugin uses [Nominatim](https://nominatim.org) to calculate geo-coordinates from street or house addresses, also known as geocoding. Currently, there is no option to use an alternative geo-coding service. We chose Nominatim because the service is open source and is operated with the support of the OSM community.

#### What is being sent

When requesting the remote Nominatim server, a plugin- and site-specific `UserAgent` header and a `Referer` header are set, and thus no data from the request header is forwarded. Because of this, no device-fingerprinting can take place. Both values help the Nominatim server to trace back the requests of the installation in order to prevent abuse and are also part of Nominatim's terms of use.