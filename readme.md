# GatewayAPI PHP Library

This library will allow you to integrate the **GatewayAPI.com** API in your project using modern PHP7 and an OOP structure.
For full description of their API, error codes and so on, see: https://gatewayapi.com/docs.

### Prerequisites

You need an active account at https://www.gatewayapi.com to use this library.
Once you have that, you need to generate an API key/secret pair under **API** -> **API Keys**.


### Installation

To include this in your project, install it using Composer.

As we use return types and type hints, this library requires PHP 7.1.

`composer require nickdnk/gatewayapi-php`

### How to use

```php
use nickdnk\GatewayAPI\GatewayAPIHandler;
use nickdnk\GatewayAPI\Recipient;
use nickdnk\GatewayAPI\SMSMessage;

$handler = new GatewayAPIHandler('my_key', 'my_secret');

$message1 = new SMSMessage(
    
    // The message you want to send. Include any placeholder tag strings.
    'Hello, %FIRSTNAME%! Your code is: %CODE%.',
    
    // The name of the sender as seen by the recipient.
    // 1-11 ASCII characters, spaces are removed. 
    'MyService',

    // An array containing the recipient(s) you want to send the message
    // to. You can also pass an empty array and add recipients later on.
    // See the example below the constructor.
    [new Recipient(4512345678, ['John', '23523'])],

    // Arbitrary label added to your message(s).
    // Pass null if you don't need this.
    'customer1',

    // The strings to replace in your message with tag values for each
    // recipient. Pass an empty array if you don't use tags in your message.
    ['%FIRSTNAME%', '%CODE%'],
    
    // The UNIX timestamp for when you want your message to be sent.
    // Pass null to send immediately. This should *not* be less than
    // the current UNIX time. This example sends in 1 hour.
    time() + 3600,
    
    // The message class to use. Note that prices vary. The secret class
    // requires approval by GatewayAPI on your account before you can use
    // it. Otherwise you will get an error.
    SMSMessage::CLASS_STANDARD

);

// If you prefer a shorter constructor, you can use the default values
// and set your parameters after construction.
$message2 = new SMSMessage('Hello %NAME%! Your code is: %CODE%', 'MyService');

$message2->setSendTime(time() + 3600);
$message2->setClass(SMSMessage::CLASS_PREMIUM);
$message2->setUserReference('customer1');
$message2->setTags(['%NAME%', '%CODE%']);

$message2->addRecipient(new Recipient(4587652222, ['Martha', '42442']));

try {

    // Note that a single SMSMessage must not contain more than 10,000
    // recipients. If you want to send to more than 10,000 you should split
    // your recipients into several SMSMessages. You can, however, send
    // multiple SMSMessages in a single request.
    $result = $handler->deliverMessages(
        [
            $message1,
            $message2
        ]
    );

    // The total cost of this request.
    $totalCost = $result->getTotalCost();

    // Currency you were charged in.
    $currency = $result->getCurrency();

    // The number of messages sent. For a message that's 3 SMSes long
    // with 1000 recipients, this will be 3000.
    $totalMessagesSent = $result->getTotalSMSCount();

    // Messages sent to UK only.
    $ukMessages = isset($result->getCountries()['UK'])
    ? $result->getCountries()['UK']
    : 0;

} catch (nickdnk\GatewayAPI\Exceptions\InsufficientFundsException $e) {

    /**
     * Your account has insufficient funds and you cannot send the
     * message(s) before you buy more credits at gatewayapi.com.
     */

} catch (nickdnk\GatewayAPI\Exceptions\MessageException $e) {

    /**
     * This should not happen if you properly use the library and pass
     * correct data into the functions, but it indicates that whatever
     * you're doing is not allowed by GatewayAPI. It can happen if you
     * add the same phone number (recipient) twice to an SMSMessage. To
     * prevent this, add multiple SMSMessages to the array of messages
     * to be sent, or call the send method twice.
     *
     * This can also happen if you don't use the tags function correctly,
     * such as not providing a tag value for a recipient within a message
     * that has a defined set of tags, or if you provide a tag value as
     * an integer.
     */

    // The error code (may be null)
    $e->getGatewayAPIErrorCode();
    
    // Error message, if present.
    $e->getMessage();

    // Full response.
    $e->getResponse()->getBody();

} catch (nickdnk\GatewayAPI\Exceptions\UnauthorizedException $e) {

    /**
     * There is something wrong with your credentials or your IP is
     * banned. Make sure you API key and secret are valid or contact
     * customer support.
     */

    // The error code (may be null)
    $e->getGatewayAPIErrorCode();
    
    // Error message, if present.
    $e->getMessage();
        
    // Full response.
    $e->getResponse()->getBody();

} catch (nickdnk\GatewayAPI\Exceptions\ConnectionException $e) {

    /**
     * Connection to GatewayAPI failed or timed out. Try again or
     * check their server status at https://status.gatewayapi.com/
     */
    
    // Error message, if present.
    $e->getMessage();
    
    // The error message and response object will always be null.

} catch (nickdnk\GatewayAPI\Exceptions\BaseException $e) {

    /**
     * Something else is wrong.
     * All exceptions inherit from this one, so you can catch this error
     * to handle all errors the same way or implement your own error
     * handler based on the error code. Remember to check for nulls.
     */

    // The error code (may be null).
    $e->getGatewayAPIErrorCode();
    
    // Error message, if present.
    $e->getMessage();

    // HTTP response (may also be null on connection errors).
    $response = $e->getResponse();
    
    if ($response !== null) {
        $response->getBody();
        $response->getStatusCode();
    }

}

// You cancel an SMS batch as well, using the IDs returned.

try {
    
    $handler->cancelScheduledMessages($result->getMessageIds());
    
} catch (nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException $e) {

    /**
     * If you attempt to cancel an SMS that has already been sent or 
     * canceled.
     */

    // The error code (may be null)
    $e->getGatewayAPIErrorCode();
    
    // Error message, if present.
    $e->getMessage();
        
    // Full response.
    $e->getResponse()->getBody();

} catch (nickdnk\GatewayAPI\Exceptions\ConnectionException $e) {

    /**
     * Connection to GatewayAPI failed or timed out. Try again or
     * check their server status at https://status.gatewayapi.com/
     */
    
    // Error message, if present.
    $e->getMessage();
    
    // The error message and response object will always be null.

} catch (nickdnk\GatewayAPI\Exceptions\BaseException $e) {

    /**
     * Something else is wrong.
     * All exceptions inherit from this one, so you can catch this error
     * to handle all errors the same way or implement your own error
     * handler based on the error code. Remember to check for nulls.
     */

    // The error code (may be null).
    $e->getGatewayAPIErrorCode();
    
    // Error message, if present.
    $e->getMessage();

    // HTTP response (may also be null on connection errors).
    $response = $e->getResponse();
    
    if ($response !== null) {
        $response->getBody();
        $response->getStatusCode();
    }

}
```

### Contact

nickdnk (at) hotmail.com

Use this library at your own risk. PRs are welcome :)