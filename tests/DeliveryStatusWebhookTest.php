<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Psr7\Request;
use nickdnk\GatewayAPI\Entities\Webhooks\DeliveryStatusWebhook;
use nickdnk\GatewayAPI\Exceptions\WebhookException;
use nickdnk\GatewayAPI\Entities\Webhooks\Webhook;
use PHPUnit\Framework\TestCase;

class DeliveryStatusWebhookTest extends TestCase
{

    private const VALID_JWT = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJ0aW1lIjoxNTIyNzY0MDYyLCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6ImVycm9yIHRleHQiLCJjb2RlIjoiZXJyb3IgY29kZSIsImNvdW50cnlfcHJlZml4Ijo0NSwiY291bnRyeV9jb2RlIjoiREsiLCJjaGFyZ2Vfc3RhdHVzIjoiQVVUSE9SSVpFRCIsInVzZXJyZWYiOiJhIHJlZmVyZW5jZSJ9.LgbbsxAj61SQhDA0A3dGlcT8OZN2UJsTgBiifNHEeh8';

    public function testConstructFromRequest()
    {

        $request = new Request(
            'POST', 'https://localhost', ['X-Gwapi-Signature' => self::VALID_JWT]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $webhook = Webhook::constructFromRequest($request, 'secret');

        $this->assertInstanceOf(DeliveryStatusWebhook::class, $webhook);

        $this->assertEquals(2381703, $webhook->getMessageId());
        $this->assertEquals(4542609045, $webhook->getPhoneNumber());
        $this->assertEquals(1522764062, $webhook->getTimestamp());
        $this->assertEquals(DeliveryStatusWebhook::STATUS_DELIVERED, $webhook->getStatus());
        $this->assertEquals('error text', $webhook->getErrorDescription());
        $this->assertEquals('error code', $webhook->getErrorCode());
        $this->assertEquals('a reference', $webhook->getUserReference());
        $this->assertEquals('DK', $webhook->getCountryCode());
        $this->assertEquals(45, $webhook->getCountryPrefix());
        $this->assertEquals(DeliveryStatusWebhook::CHARGE_STATUS_AUTHORIZED, $webhook->getChargeStatus());

    }


    public function testMissingJWTHeader()
    {

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Missing webhook JWT header');

        $request = new Request(
            'POST', 'https://localhost'
        );

        Webhook::constructFromRequest($request, 'whatever');

    }


    public function testMissingRequiredKeys()
    {

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Webhook missing required keys');

        $request = new Request(
            'POST', 'https://localhost', [
                      'X-Gwapi-Signature' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6bnVsbCwiY29kZSI6bnVsbCwidXNlcnJlZiI6bnVsbCwiY2FsbGJhY2tfdXJsIjoiaHR0cDovL2JiYWY3MTQyLm5ncm9rLmlvIiwiYXBpIjo0fQ.6lYoTn05tdJZIke3pxZg-dCxLjCeaOuWgjk7Ln6UQtA'
                  ]
        );

        Webhook::constructFromRequest($request, 'secret');

    }

    public function testInvalidJWT()
    {

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Failed to parse');

        $request = new Request(
            'POST', 'https://localhost', ['X-Gwapi-Signature' => 'not_valid']
        );

        Webhook::constructFromRequest($request, 'whatever');

    }


    public function testInvalidSignature()
    {

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('failed signature');

        $request = new Request(
            'POST', 'https://localhost', ['X-Gwapi-Signature' => self::VALID_JWT]
        );

        Webhook::constructFromRequest($request, 'wrong');

    }


    public function testHS512()
    {

        $request = new Request(
            'POST', 'https://localhost', [
                      'X-Gwapi-Signature' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJ0aW1lIjoxNTIyNzY0MDYyLCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6bnVsbCwiY29kZSI6bnVsbCwidXNlcnJlZiI6bnVsbCwiY2FsbGJhY2tfdXJsIjoiaHR0cDovL2JiYWY3MTQyLm5ncm9rLmlvIiwiYXBpIjo0fQ.o3FGANwBGxEAK2tfFZtp9ZraDxUgwypBj0dq1C13IWEddpxcth8dQHngaQMq6FtbGpDO80pyMeedDSndzKMoag'
                  ]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $webhook = Webhook::constructFromRequest($request, 'secret');

        $this->assertEquals(2381703, $webhook->getMessageId());

    }

    public function testHS384()
    {

        $request = new Request(
            'POST', 'https://localhost', [
                      'X-Gwapi-Signature' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJ0aW1lIjoxNTIyNzY0MDYyLCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6bnVsbCwiY29kZSI6bnVsbCwidXNlcnJlZiI6bnVsbCwiY2FsbGJhY2tfdXJsIjoiaHR0cDovL2JiYWY3MTQyLm5ncm9rLmlvIiwiYXBpIjo0fQ.2itUNDqtCZyE6g320Tn8RRd0rykJEoFtVeffPzUKLmMGwrG3fCXufwbpc-43dYGA'
                  ]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $webhook = Webhook::constructFromRequest($request, 'secret');

        $this->assertEquals(2381703, $webhook->getMessageId());

    }

    public function testNoAlgorithm()
    {

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('failed signature');

        $request = new Request(
            'POST', 'https://localhost', [
                      'X-Gwapi-Signature' => 'eyJ0eXBlIjoiSldUIiwiYWxnIjoibm9uZSJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJ0aW1lIjoxNTIyNzY0MDYyLCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6bnVsbCwiY29kZSI6bnVsbCwidXNlcnJlZiI6bnVsbCwiY2FsbGJhY2tfdXJsIjoiaHR0cDovL2JiYWY3MTQyLm5ncm9rLmlvIiwiYXBpIjo0fQ.KdfDH65bnQtgxEkFnpAQodOciAJedZFB13r9wEo8t3Y'
                  ]
        );

        Webhook::constructFromRequest($request, 'secret');

    }
}
