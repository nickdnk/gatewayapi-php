# Changelog

## 1.1.0 - 2020-03-03
* Added more exceptions; `GatewayServerException` and `GatewayRequestException`. These allow you to distinguish between actual server errors and 400-range client/library errors. Backwards-compatibility is maintained as these extend `BaseException`.
* Added proper handling of json_decode()-errors using json_last_error() instead of falsy-checks.
* Cleaned up response parsing and added more tests.