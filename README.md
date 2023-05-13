# Worldline Online Payments

## Redirect Payment (Single payment buttons)

[![M2 Coding Standard](https://github.com/wl-online-payments-direct/plugin-magento-redirect-payments/actions/workflows/coding-standard.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-redirect-payments/actions/workflows/coding-standard.yml)
[![M2 Mess Detector](https://github.com/wl-online-payments-direct/plugin-magento-redirect-payments/actions/workflows/mess-detector.yml/badge.svg?branch=develop)](https://github.com/wl-online-payments-direct/plugin-magento-redirect-payments/actions/workflows/mess-detector.yml)

This module is an extension of the [hosted checkout](https://github.com/wl-online-payments-direct/plugin-magento-hostedcheckout) Worldline payment solution.

To install this solution, you may use
[adobe commerce marketplace](https://marketplace.magento.com/worldline-module-magento-payment.html)
or install it from the GitHub.

This solution is also included into [main plugin for adobe commerce](https://github.com/wl-online-payments-direct/plugin-magento).

### Change log:

#### 1.8.2
- Add support for the 5.3.0 version of PHP SDK.
- Fix connection credential caching.

#### 1.8.1
- Add support for the 5.1.0 version of PHP SDK.
- Add integration tests.
- General code improvements.

#### 1.8.0
- Add support for Magento 2.4.6.
- Add support for the 5.0.0 version of PHP SDK.
- Add a setting for Oney3x4x to manage the “Oney3x4x payment option” parameter.
- Hide Apple Pay if the customer cannot pay with it.
- Add integration tests.
- General code improvements.

#### 1.7.2
- Add fix for Adobe Commerce cloud instances.

#### 1.7.1
- Add backend address validation before payments.

#### 1.7.0
- Add surcharge functionality (for the Australian market).
- Add Sepa Direct Debit payment method.
- Add the ability to save the Sepa Direct Debit mandate and use it through the Magento vault.
- Improvements of the Oney3x4x payment method.
- Extract GraphQl into a dedicated extension.
- General code improvements and bug fixes.

#### 1.6.1
- Support the 13.0.0 version of PWA.

#### 1.6.0
- Add Multibanco payment method.
- Add price restrictions for currencies having specific decimals rules (like JPY).
- Move 3-D Secure settings to the general tab.
- Change names and tooltips of the 3-D Secure settings.
- General code improvements and bug fixes

#### 1.5.1
- Rise core version.

#### 1.5.0
- Add the "Mealvouchers" payment method.
- Improve cancel and void actions logic.
- Add uninstall script.
- Update release notes.
- General code improvements and bug fixes.

#### 1.4.0
- Add a feature to request 3DS exemption for transactions below 30 EUR.
- General code improvements and bug fixes.

#### 1.3.1
- Bug fixes.
- GraphQl improvements and support.

#### 1.3.0
- Improve configuration settings.
- Option added to enforce Strong Customer Authentication for every 3DS request.
- Add new payment product Oney.
- General code improvements and bug fixes.
- Improvements and support for 2.3.x magento versions.

#### 1.2.1
- Improve work for multi website instances.

#### 1.2.0
- Improve the "waiting" page.
- Add the "pending" page.

#### 1.1.0
- General improvements and bug fixes.

#### 1.0.1
- PWA improvements and support.
- Bug fixes and general code improvements.

#### 1.0.0
- New Redirect payments: integrate single payment buttons directly on Magento checkout.
