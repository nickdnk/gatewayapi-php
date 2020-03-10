# Changelog

## 2.0.1 - 2020-03-10
* Added `setRecipients()` to `SMSMessage`.

## 2.0 - 2020-03-05
* Restructured namespaces for entity- and webhook-classes.
* Removed `PastSendTimeException` and its handling as the API has changed so it no longer works.
* Added `SuccessfulResponseParsingException`. You should check for this if you implement automatic retries of failed requests.
* Made `BaseException` abstract.
* Added `Prices` entity for price response.
* Added Coveralls and Travis as well as more tests.

## 1.1 - 2020-03-03
* Added more exceptions; `GatewayServerException` and `GatewayRequestException`. These allow you to distinguish between actual server errors and 400-range client/library errors. Backwards-compatibility is maintained as these extend `BaseException`.
* Added proper handling of `json_decode()`-errors using `json_last_error()` instead of falsy-checks.
* Cleaned up response parsing and added more tests.