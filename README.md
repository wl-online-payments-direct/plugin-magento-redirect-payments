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

#### 1.36.0
- Fix: Do not allow usage of decimals in the object cardPaymentMethodSpecificInput.paymentProduct130SpecificInput.threeDSecure.numberOfItems

#### 1.35.0
- Added: Possibility to auto-include primary webhooks URL in the payload of payment request, and to configure up to 4 additional endpoints.
- Fix Worldline Block/Info.php not compatible with Magento core Payment/Block/Info.php.

#### 1.34.0
- Improved: Data mapping to flag correctly exemptions requests to 3-D Secure.

#### 1.33.0
- Add new payment method: Pledg

#### 1.32.0
- Remove MealVouchers configuration from hosted checkout
- Fix mobile payment method information not being shown in order details

#### 1.31.0
- Update payment brand logos

#### 1.30.0
- Add quote ID to request payload
- Fix wrong IP address being sent on checkout
- Decrease maximum payment method logos
- Add compatibility with 2.4.8-p2

#### 1.29.0
- Fix issue with sending email

#### 1.28.0
- Fix wrong handling of payment specific information on order page

#### 1.27.0
- Fix comma separated email validation in notification settings

#### 1.26.0
- Fix issue with showing split payment amounts on order details page for Mealvoucher transactions
- Fix issue with showing Mealvoucher in full redirect

#### 1.25.0
- Fix logo issue for CB on checkout page
- Fix PHP >= 8.2 issue with not sending parameter by reference

#### 1.24.0
- Add Mealvoucher payment product
- Add CVCO (Cheque Vacances Connect) payment product

#### 1.23.0
- Add compatibility with PHP 8.4
- Update SDK version

#### 1.22.0
- Fixed order creation using Google Pay & Apple Pay

#### 1.21.0
- Update plugin translations

#### 1.20.0
- Added 3DS exemption types to the plugin

#### 1.19.0
- Fixed validation for HTML template ID configuration. It is no longer required to have extension on HTML templates.
- Fixed issue where items quantities in decimals were not taken into account.
- Improved handling of orders where the total amount does not match the sum of line items amount due to the rounding.

#### 1.18.0
- Fixed issue where FPT (Fixed Product Tax) rates were not taken into account.
- Update "wl-online-payments-direct/sdk-php" library to 5.16.1

#### 1.17.0
- Improved display of shipping costs on the payment page for Hosted Checkout and Redirect Payment.

#### 1.16.0
- Added trusted URLs to the CSP whitelist.
- Improved reliability of fallback cron job.
- Fixed credentials caching issue when simultaneously processing refunds for multiple merchant IDs.

#### 1.15.0
- Improved the order creation process by tracking multiple paymentIDs.
- Improved logging and exception handling when multiple payments are done for a single order.

#### 1.14.0
- Added new payment method "Bank Transfer by Worldline".
- Added the "Contact email" field to the feature suggestion form.
- Added compatibility with Php Sdk 5.10.0.
- Replaced legacy Alipay payment method with the new Alipay+.
- Replaced legacy WeChat Pay payment method with the new version.
- Fixed validation error when placing orders with Virtual/downloadable products.
- Fixed error when adding new shipping address on checkout.

#### 1.13.0
- Added email to customer and “Copy To” for "Auto Refund For Out Of Stock Orders" notifications.
- Added translations for French (Belgium), French (Switzerland) and Dutch (Belgium).
- Improved notifications so they are only sent once per event.
- Improved "Failed Orders Notifications" to avoid triggering on transaction status 46.
- Fixed "Redirect Payments" display issue after customer modifies shipping options.
- Fixed server error on checkout page when "Specific Currencies" are not aligned with Magento’s non-default currencies.

#### 1.12.0
- Added "Session Timeout" configuration for the hosted checkout page.
- Added "Allowed Number Of Payment Attempts" configuration for the hosted checkout page.
- Added compatibility with Php Sdk 5.8.2.
- Added refund refused notifications functionality.
- Fixed update of the credit memo status when the refund request was refused by acquirer.

#### 1.11.1
- Fixed issue with partial invoices and partial credit memos.
- Fixed transaction ID value for request to check if payment can be cancelled.

#### 1.11.0
- Added own branded gift card compatibility for Intersolve payment method.
- Added compatibility with Php Sdk 5.7.0.
- Modified plugin tab "dynamic order status synchronization" to “Settings & Notifications”.
- Fixed value determination process for "AddressIndicator" parameter.
- Fixed issues with creating orders by cron.
- Fixed issue with Magento confirmation page when using PayPal payment method.
- Fixed issue with auto refund for out-of-stock feature.
- Fixed issue when using a database prefix.

#### 1.10.0
- Added new payment method “Union Pay International".
- Added new payment method “Przelewy24".
- Added new payment method “EPS".
- Added new payment method “Twint".
- Added compatibility with Php Sdk 5.6.0.
- Added compatibility with Amasty Subscriptions & Recurring Payments extension 1.6.15.
- Improved plugin landing page "About Worldline".
- Improved Hosted Tokenization error message when transaction is declined.
- Improved concatenation of streetline1 and streetline2 for billing & shipping address.

#### 1.9.0
- Added new payment method “Giftcard Limonetik".
- Added new setting "Enable Sending Payment Refused Emails".
- Improved handling of Magento 2 display errors.
- Fixed hosted tokenization js link for production transactions.
- Fixed order creation issue on successful transactions.
- Fixed webhooks issue for rejected transactions with empty refund object.
- General code improvements.

#### 1.8.3
- Fixed issue of products with special pricing not displaying the original price in order view.
- Fixed issue with configurable product on cart restoration when user clicks the browser back button.
- Fixed issue with last payment id not fetched properly.
- Fixed issue where carts are restored incompletely.
- Fixed issue when customer attribute doesn't display in order after paying.
- Added customer address attributes validation before placing order.
- Added a setting to stop sending refusal emails.
- Added compatibility with Php Sdk 5.4.0.

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
