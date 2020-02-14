<?php


use GuzzleHttp\Psr7\Request;
use nickdnk\GatewayAPI\DeliveryStatusWebhook;
use PHPUnit\Framework\TestCase;

class DeliveryStatusWebhookTest extends TestCase
{

    private const VALID_JWT = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJ0aW1lIjoxNTIyNzY0MDYyLCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6ImVycm9yIHRleHQiLCJjb2RlIjoiZXJyb3IgY29kZSIsImNvdW50cnlfcHJlZml4Ijo0NSwiY291bnRyeV9jb2RlIjoiREsiLCJjaGFyZ2Vfc3RhdHVzIjoiQVVUSE9SSVpFRCIsInVzZXJyZWYiOiJhIHJlZmVyZW5jZSJ9.LgbbsxAj61SQhDA0A3dGlcT8OZN2UJsTgBiifNHEeh8';

    /**
     * @throws nickdnk\GatewayAPI\Exceptions\WebhookException
     */
    public function testConstructFromRequest()
    {

        $request = new Request(
            'POST', 'https://localhost', ['X-Gwapi-Signature' => self::VALID_JWT]
        );

        $webhook = DeliveryStatusWebhook::constructFromRequest($request, 'secret');

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

    /**
     * @throws nickdnk\GatewayAPI\Exceptions\WebhookException
     */
    public function testMissingJWTHeader()
    {

        $this->expectException(nickdnk\GatewayAPI\Exceptions\WebhookException::class);
        $this->expectExceptionMessage('Missing webhook JWT header');

        $request = new Request(
            'POST', 'https://localhost'
        );

        DeliveryStatusWebhook::constructFromRequest($request, 'whatever');

    }

    /**
     * @throws nickdnk\GatewayAPI\Exceptions\WebhookException
     */
    public function testMissingRequiredKeys()
    {

        $this->expectException(nickdnk\GatewayAPI\Exceptions\WebhookException::class);
        $this->expectExceptionMessage('Webhook missing required keys');

        $request = new Request(
            'POST', 'https://localhost', [
                      'X-Gwapi-Signature' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6bnVsbCwiY29kZSI6bnVsbCwidXNlcnJlZiI6bnVsbCwiY2FsbGJhY2tfdXJsIjoiaHR0cDovL2JiYWY3MTQyLm5ncm9rLmlvIiwiYXBpIjo0fQ.6lYoTn05tdJZIke3pxZg-dCxLjCeaOuWgjk7Ln6UQtA'
                  ]
        );

        DeliveryStatusWebhook::constructFromRequest($request, 'secret');

    }

    /**
     * @throws nickdnk\GatewayAPI\Exceptions\WebhookException
     */
    public function testInvalidJWT()
    {

        $this->expectException(nickdnk\GatewayAPI\Exceptions\WebhookException::class);
        $this->expectExceptionMessage('Failed to parse');

        $request = new Request(
            'POST', 'https://localhost', ['X-Gwapi-Signature' => 'not_valid']
        );

        DeliveryStatusWebhook::constructFromRequest($request, 'whatever');

    }

    /**
     * @throws nickdnk\GatewayAPI\Exceptions\WebhookException
     */
    public function testInvalidSignature()
    {

        $this->expectException(nickdnk\GatewayAPI\Exceptions\WebhookException::class);
        $this->expectExceptionMessage('failed signature');

        $request = new Request(
            'POST', 'https://localhost', ['X-Gwapi-Signature' => self::VALID_JWT]
        );

        DeliveryStatusWebhook::constructFromRequest($request, 'wrong');

    }

    /**
     * @throws nickdnk\GatewayAPI\Exceptions\WebhookException
     */
    public function testHS512()
    {

        $request = new Request(
            'POST', 'https://localhost', [
                      'X-Gwapi-Signature' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJpZCI6MjM4MTcwMywibXNpc2RuIjo0NTQyNjA5MDQ1LCJ0aW1lIjoxNTIyNzY0MDYyLCJzdGF0dXMiOiJERUxJVkVSRUQiLCJlcnJvciI6bnVsbCwiY29kZSI6bnVsbCwidXNlcnJlZiI6bnVsbCwiY2FsbGJhY2tfdXJsIjoiaHR0cDovL2JiYWY3MTQyLm5ncm9rLmlvIiwiYXBpIjo0fQ.o3FGANwBGxEAK2tfFZtp9ZraDxUgwypBj0dq1C13IWEddpxcth8dQHngaQMq6FtbGpDO80pyMeedDSndzKMoag'
                  ]
        );

        $webhook = DeliveryStatusWebhook::constructFromRequest($request, 'secret');

        $this->assertEquals(2381703, $webhook->getMessageId());

    }
}
