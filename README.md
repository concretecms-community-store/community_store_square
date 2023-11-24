# Community Store Square
Square payment add-on for Community Store for Concrete CMS
https://squareup.com/

Requires version 2.5+ of Community Store

Supports credit card payment via the Web Payments SDK, offering an in-checkout card entry form: https://developer.squareup.com/docs/web-payments/take-card-payment
Additionally support Apple Pay and Google Pay.

## Setup
Install Community Store First.

Download a 'release' zip of the add-on, unzip this to the packages folder of your Concrete CMS install (alongside the community_store folder) and install via the dashboard.

In Square's dashboard, create a new 'App' at https://developer.squareup.com/apps, and copy from this the Application ID and Access Token values into the configuration for Square within Community Store's settings.
The Location ID must also be entered and this value is located under the Locations list in the Square dashboard.

## Development

If you are directly cloning this repo for developing/testing purposes, note that this add-on uses [Composer](https://getcomposer.org/) to install third-party librares. Run composer at the root of the add-on folder before installing:

        composer install


