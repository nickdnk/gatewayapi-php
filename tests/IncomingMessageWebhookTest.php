<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Psr7\Request;
use nickdnk\GatewayAPI\Entities\Webhooks\IncomingMessageWebhook;
use nickdnk\GatewayAPI\Entities\Webhooks\Webhook;
use nickdnk\GatewayAPI\Exceptions\WebhookException;
use PHPUnit\Framework\TestCase;

class IncomingMessageWebhookTest extends TestCase
{

    private const VALID_JWT = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpZCI6MTAwMDAwMSwibXNpc2RuIjo0NTg3NjU0MzIxLCJyZWNlaXZlciI6NDUxMjA0LCJtZXNzYWdlIjoiZm9vIEhlbGxvIFdvcmxkIiwic2VudHRpbWUiOjE0NTAwMDAwMDAsIndlYmhvb2tfbGFiZWwiOiJ0ZXN0IiwiY291bnRyeV9jb2RlIjoiREsiLCJjb3VudHJ5X3ByZWZpeCI6NDUsInNlbmRlciI6InRlc3QiLCJ2YWxpZGl0eV9wZXJpb2QiOjM0MzQsImVuY29kaW5nIjoic3RyaW5nIiwibWNjIjoxMjMsIm1uYyI6MTI1LCJwYXlsb2FkIjoiYmluIiwidWRoIjoidWRoIHN0cmluZyJ9._Kk85q5mTBwnLi17mMVu7RmUDoFHFjsSuzjrVjmaH-M';

    public function testConstructFromRequest()
    {

        $request = new Request(
            'POST', 'https://localhost', ['X-Gwapi-Signature' => self::VALID_JWT]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $webhook = Webhook::constructFromRequest($request, 'secret');

        $this->assertTrue($webhook instanceof IncomingMessageWebhook);

        $this->assertEquals(1000001, $webhook->getMessageId());
        $this->assertEquals(4587654321, $webhook->getPhoneNumber());
        $this->assertEquals(1450000000, $webhook->getTimestamp());
        $this->assertEquals('foo Hello World', $webhook->getMessageText());
        $this->assertEquals('test', $webhook->getWebhookLabel());
        $this->assertEquals(451204, $webhook->getReceiver());
        $this->assertEquals('DK', $webhook->getCountryCode());
        $this->assertEquals(45, $webhook->getCountryPrefix());
        $this->assertEquals(123, $webhook->getMcc());
        $this->assertEquals(125, $webhook->getMnc());
        $this->assertEquals('bin', $webhook->getPayload());
        $this->assertEquals('string', $webhook->getEncoding());
        $this->assertEquals(3434, $webhook->getValidityPeriod());
        $this->assertEquals('test', $webhook->getSenderName());
        $this->assertEquals('udh string', $webhook->getUdh());

    }

    public function testInvalidArray()
    {

        $this->expectException(WebhookException::class);

        IncomingMessageWebhook::constructFromArray([
            'this' => 'is not valid'
        ]);

    }
}
