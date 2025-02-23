[![Build Status](https://github.com/nickdnk/gatewayapi-php/actions/workflows/test.yml/badge.svg?branch=master)](https://github.com/nickdnk/gatewayapi-php/actions)
[![Coverage Status](https://coveralls.io/repos/github/nickdnk/gatewayapi-php/badge.svg?branch=master)](https://coveralls.io/github/nickdnk/gatewayapi-php?branch=master)
# GatewayAPI PHP Library

This library will allow you to integrate the **GatewayAPI.com** API in your project using modern PHP.
For full description of their API, error codes and so on, see: <https://gatewayapi.com/docs>.

### Prerequisites

You need an active account at <https://www.gatewayapi.com> to use this library.
Once you have that you need to generate an API key/secret pair under **API** -> **API Keys**.


### Installation

To include this in your project, install it using Composer.

This library requires PHP >= 7.3 and is tested against 8.0, 8.1, 8.2, 8.3 and 8.4.

`composer require nickdnk/gatewayapi-php`

### How to use

#### Example #1: Sending SMS

```php
use nickdnk\GatewayAPI\GatewayAPIHandler;
use nickdnk\GatewayAPI\Entities\Request\Recipient;
use nickdnk\GatewayAPI\Entities\Request\SMSMessage;

// Pass `true` as the third parameter to use GatewayAPI in EU-only mode.
// This requires an EU account. See https://gatewayapi.com/blog/new-eu-setup-for-gatewayapi-customers/.
$handler = new GatewayAPIHandler('my_key', 'my_secret', false);

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
    // it, otherwise you will get an error.
    SMSMessage::CLASS_STANDARD,

    // The encoding of the message. Use Unicode to allow lowercase special characters or emojis.
    SMSMessage::ENCODING_UNICODE

);

// If you prefer a shorter constructor, you can use the default values
// and set your parameters after construction.
$message2 = new SMSMessage('Hello %NAME%! Your code is: %CODE%', 'MyService');

$message2->setSendTime(time() + 3600);
$message2->setClass(SMSMessage::CLASS_PREMIUM);
$message2->setUserReference('customer1');
$message2->setTags(['%NAME%', '%CODE%']);
$message2->setCallbackUrl('https://example.com/callback');
$message2->setEncoding(SMSMessage::ENCODING_UNICODE);

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
    
    // All message IDs returned.
    $result->getMessageIds();

    // The total cost of this request (all message IDs combined).
    $result->getTotalCost();

    // Currency you were charged in.
    $result->getCurrency();

    // The number of messages sent. For a message that's 3 SMSes long
    // with 1000 recipients, this will be 3000.
    $totalMessagesSent = $result->getTotalSMSCount();

    // Messages sent to UK only.
    $ukMessages = isset($result->getCountries()['UK'])
    ? $result->getCountries()['UK']
    : 0;

} catch (\nickdnk\GatewayAPI\Exceptions\InsufficientFundsException $e) {

    /**
     * Extends GatewayRequestException.
     * 
     * Your account has insufficient funds and you cannot send the
     * message(s) before you buy more credits at gatewayapi.com.
     * 
     * The request body can be retried after you top up your balance.
     */

} catch (\nickdnk\GatewayAPI\Exceptions\MessageException $e) {

    /**
     * Extends GatewayRequestException.
     * 
     * This should not happen if you properly use the library and pass
     * correct data into the functions, but it indicates that whatever
     * you're doing is not allowed by GatewayAPI.
     * 
     * It can happen if you add the same phone number (recipient) twice 
     * to an SMSMessage or if you don't use the tags function correctly,
     * such as not providing a tag value for a recipient within a message
     * that has a defined set of tags, or if you provide a tag value as
     * an integer.
     * 
     * To add the same phone number twice to one request it must be in
     * different SMSMessage objects.
     * 
     * Requests that throw this exception should *not* be retried!
     */

    // The error code (may be null)
    $e->getGatewayAPIErrorCode();
    
    // Error message, if present.
    $e->getMessage();

    // Full response.
    $e->getResponse()->getBody();

} catch (\nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException $e) {

    /**
     * Extends GatewayRequestException.
     * 
     * If you implement automatic retries of failed requests, you should
     * check for this exception. It is unlikely to ever occur, but it
     * could happen if GatewayAPI changed their API or there was an error
     * in the library. This could potentially trigger retries for requests
     * that succeeded which would be expensive as well as problematic
     * for recipients.
     */
    
    // Error message.
    $e->getMessage();

    // Full response.
    $e->getResponse()->getBody();

} catch (\nickdnk\GatewayAPI\Exceptions\UnauthorizedException $e) {

    /**
     * Extends GatewayRequestException.
     * 
     * Something is wrong with your credentials or your IP is
     * banned. Make sure you API key and secret are valid or contact
     * customer support.
     *
     * The request body can be retried if you provide different
     * credentials (or fix whatever is wrong).
     */

    // The error code (may be null)
    $e->getGatewayAPIErrorCode();
    
    // Error message, if present.
    $e->getMessage();
        
    // Full response.
    $e->getResponse()->getBody();

} catch (\nickdnk\GatewayAPI\Exceptions\GatewayServerException $e) {
    
    /**
     * Extends GatewayRequestException.
     * 
     * Something is wrong with GatewayAPI.com. This exception simply
     * extends GatewayRequestException but only applies to 500-range
     * errors.
     *
     * The request body can (most likely) be retried.
     */
    
     // The error code (may be null)
    $e->getGatewayAPIErrorCode();
    
    // Error message.
    $e->getMessage();
        
    // Full response.
    $e->getResponse()->getBody();
    
} catch (\nickdnk\GatewayAPI\Exceptions\ConnectionException $e) {

    /**
     * Connection to GatewayAPI failed or timed out. Try again or
     * check their server status at https://status.gatewayapi.com/
     *
     * The request can/should be retried. This library does not 
     * automatically retry requests that fail for this reason. 
     */
    
    // Error message.
    $e->getMessage();

} catch (\nickdnk\GatewayAPI\Exceptions\BaseException $e) {

    /**
     * Something else is wrong.
     * All exceptions inherit from this one, so you can catch this error
     * to handle all errors the same way or implement your own error
     * handler based on the error code. Remember to check for nulls.
     * 
     * This exception is abstract, so you can check which class it is
     * and go from there.
     */
    
    // Error message.
    $e->getMessage();

    if ($e instanceof \nickdnk\GatewayAPI\Exceptions\GatewayServerException) {
        
        // The error code (may be null).
        $e->getGatewayAPIErrorCode();

        $response = $e->getResponse();

        $response->getBody();
        $response->getStatusCode();

    }

}
```
#### Example #2: Canceling scheduled messages
You can cancel a scheduled SMS based on the ID returned when sending.
As this method creates a pool of requests (1 per message ID) it does
not throw exceptions but returns an array of `CancelResult`. Each of
these contain the status and (if failed) exception of the request.
```php
use nickdnk\GatewayAPI\Entities\CancelResult;
use nickdnk\GatewayAPI\GatewayAPIHandler;

$handler = new GatewayAPIHandler('my_key', 'my_secret');
$results = $handler->cancelScheduledMessages([1757284, 1757288]);

foreach ($results as $cancelResult) {
    
    // The ID of the canceled message is always available.
    $cancelResult->getMessageId();
    
    if ($cancelResult->getStatus() === CancelResult::STATUS_SUCCEEDED) {
        
        // Success. Obviously.
        
    } elseif ($cancelResult->getStatus() === CancelResult::STATUS_FAILED) {

        // Get the exception of a failed request.
        $cancelResult->getException();
        
    }

}
```
#### Example #3: Parsing webhooks
You can easily parse webhooks sent from GatewayAPI to your server
using the `Webhook` class. This uses the JWT header to ensure that
the webhook has not been tampered with and is in fact coming from
a trusted source.

To set up webhooks go to  **API** -> **Web Hooks** -> **REST**. Specify
a JWT secret under Authentication after you've created the webhook.

Two types of webhooks can be sent; delivery status notifications and
incoming messages (MO traffic). Both are parsed by `Webhook` and
returned as their corresponding class. To read incoming messages you have to
subscribe to a keyword or number under **Subscriptions** -> **Keywords / Numbers**
and assign the keyword or number to a webhook.
```php
use nickdnk\GatewayAPI\Entities\Webhooks\DeliveryStatusWebhook;
use nickdnk\GatewayAPI\Entities\Webhooks\IncomingMessageWebhook;
use nickdnk\GatewayAPI\Entities\Webhooks\Webhook;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The webhook parser is based on PSR-7 allowing you to pass a $request
 * object directly into the class and get a webhook back.
 */
function (RequestInterface $request, ResponseInterface $response) {
    
    try {

        $webhook = Webhook::constructFromRequest($request, 'my_jwt_secret');
        
        // Determine the type of webhook if you don't already know.
        if ($webhook instanceof DeliveryStatusWebhook) {
            
            $webhook->getPhoneNumber();
            $webhook->getStatus();
            
        } elseif ($webhook instanceof IncomingMessageWebhook) {
            
            $webhook->getPhoneNumber();
            $webhook->getWebhookLabel();
            $webhook->getMessageText();
            
        }
    
    } catch (\nickdnk\GatewayAPI\Exceptions\WebhookException $exception) {
        
        // Something is wrong with the webhook or it was not correctly
        // signed. Take a look at your configuration.
        $exception->getMessage();
        
    }
    
}

/**
 * Or if you don't have a PSR-7 request handy, you can pass the JWT
 * directly into this method instead. Note that the JWT contains
 * the entire payload, which is duplicated unsigned in the body of the
 * request. We don't read the request body at all.
 */

// JWT as a string, read from where-ever:
$jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';

try {
    
    $webhook = Webhook::constructFromJWT($jwt, 'my_jwt_secret');
    
} catch (\nickdnk\GatewayAPI\Exceptions\WebhookException $exception) {
    
    $exception->getMessage();
    
}
```
#### Example #4: Handling SMSMessages or Recipients as JSON
`SMSMessage` and `Recipient` are encoded into the actual JSON sent
to the API. If you put this output into a queue, or anything similar,
and want them back as PHP objects later, you can use these methods to
do so.
```php
use nickdnk\GatewayAPI\Entities\Request\Recipient;
use nickdnk\GatewayAPI\Entities\Request\SMSMessage;

$recipient = new Recipient(4587652222, ['Martha', '42442']);

$json = json_encode($recipient);

$recipient = Recipient::constructFromJSON($json);

$message = new SMSMessage('Hello %NAME%! Your code is: %CODE%', 'MyService');
$message->setSendTime(time() + 3600);
$message->setUserReference('reference');
$message->setTags(['%NAME%', '%CODE%']);
$message->setEncoding(SMSMessage::ENCODING_UNICODE);
$message->addRecipient($recipient);

$json = json_encode($message);

$smsMessage = SMSMessage::constructFromJSON($json);
$smsMessage->getMessage();
$smsMessage->getRecipients();
```

### Tests

If you want to run the unit tests that don't require credentials, simply
run `vendor/bin/phpunit` from the root of the project.

If you want to test the parts that interact with the API you must
provide credentials in `GatewayAPIHandlerTest.php` and run the above
command. Note that this sends out live SMS and will cost you 1 SMS in
credits per execution.

### Contact

You can reach me at nickdnk@hotmail.com.

Use this library at your own risk. PRs are welcome :)
