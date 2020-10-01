# Changelog

## 3.0.1 - 2020-10-01
* Added the `callback_url` parameter to `SMSMessage`. Thanks to @Matthew-Kilpatrick.
* Fixed some minor nullability and phpdoc inconsistencies.

## 3.0 - 2020-04-30
* Refactored `Constructable` into a trait.
* [BC] `Webhook` constructor is now `protected` and the subclass constructors are `final`. If you implement this class like it was meant to (using the static constructors) you won't need to make any changes to your code.

## 2.0.1 - 2020-03-10
* Added `setRecipients()` to `SMSMessage`.

## 2.0 - 2020-03-05
* [BC] Restructured namespaces for entity- and webhook-classes.
* [BC] Removed `PastSendTimeException` and its handling as the API has changed so it no longer works.
* Added `SuccessfulResponseParsingException`. You should check for this if you implement automatic retries of failed requests.
* Made `BaseException` abstract.
* Added `Prices` entity for price response.
* Added Coveralls and Travis as well as more tests.

## 1.1 - 2020-03-03
* Added more exceptions; `GatewayServerException` and `GatewayRequestException`. These allow you to distinguish between actual server errors and 400-range client/library errors. Backwards-compatibility is maintained as these extend `BaseException`.
* Added proper handling of `json_decode()`-errors using `json_last_error()` instead of falsy-checks.
* Cleaned up response parsing and added more tests.