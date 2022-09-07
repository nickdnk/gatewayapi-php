# Changelog

## 3.3.0 - 2022-09-07

* Added support for EU-only mode: https://gatewayapi.com/blog/new-eu-setup-for-gatewayapi-customers/ - pass `true` to
  the optional constructor argument and to `getPries()` to use the EU-only mode.
* Test against PHP 8.2

## 3.2.0 - 2022-02-27

* Added support for version `0.6.*` of `guzzlehttp/oauth-subscriber`.
* Switched to GitHub actions instead of Travis.

## 3.1.2 - 2021-05-10

* Added compatibility with Guzzle 7.
* Adjustments to tests and nullability.

## 3.1.1 - 2021-05-09

* Fixed use of incorrect Guzzle exception for detecting connection error vs. HTTP error.

## 3.1.0 - 2021-04-01

* Support for PHP 8.0.

## 3.0.1 - 2020-10-01

* Added the `callback_url` parameter to `SMSMessage`. Thanks to @Matthew-Kilpatrick.
* Fixed some minor nullability and phpdoc inconsistencies.

## 3.0 - 2020-04-30

* Refactored `Constructable` into a trait.
* [BC] `Webhook` constructor is now `protected` and the subclass constructors are `final`. If you implement this class
  like it was meant to (using the static constructors) you won't need to make any changes to your code.

## 2.0.1 - 2020-03-10

* Added `setRecipients()` to `SMSMessage`.

## 2.0 - 2020-03-05

* [BC] Restructured namespaces for entity- and webhook-classes.
* [BC] Removed `PastSendTimeException` and its handling as the API has changed so it no longer works.
* Added `SuccessfulResponseParsingException`. You should check for this if you implement automatic retries of failed
  requests.
* Made `BaseException` abstract.
* Added `Prices` entity for price response.
* Added Coveralls and Travis as well as more tests.

## 1.1 - 2020-03-03

* Added more exceptions; `GatewayServerException` and `GatewayRequestException`. These allow you to distinguish between
  actual server errors and 400-range client/library errors. Backwards-compatibility is maintained as these
  extend `BaseException`.
* Added proper handling of `json_decode()`-errors using `json_last_error()` instead of falsy-checks.
* Cleaned up response parsing and added more tests.
