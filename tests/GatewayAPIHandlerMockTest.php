<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Exception\ConnectException;
use InvalidArgumentException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use nickdnk\GatewayAPI\Entities\CancelResult;
use nickdnk\GatewayAPI\Entities\Request\Recipient;
use nickdnk\GatewayAPI\Entities\Request\SMSMessage;
use nickdnk\GatewayAPI\Entities\Response\AccountBalance;
use nickdnk\GatewayAPI\Entities\Response\Prices;
use nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException;
use nickdnk\GatewayAPI\Exceptions\ConnectionException;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\GatewayServerException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use nickdnk\GatewayAPI\GatewayAPIHandler;
use PHPUnit\Framework\TestCase;

/**
 * These tests cover the request and response handling without hitting the network, by passing a MockHandler into
 * GatewayAPIHandler. They run against both Guzzle 7 and Guzzle 8. For live tests against gatewayapi.com, see
 * GatewayAPIHandlerTest.
 */
class GatewayAPIHandlerMockTest extends TestCase
{

    private MockHandler $mockHandler;

    /**
     * @param array $queue Responses and/or exceptions for the MockHandler to return, in order.
     */
    private function handlerReturning(array $queue): GatewayAPIHandler
    {

        $this->mockHandler = new MockHandler($queue);

        return new GatewayAPIHandler('key', 'secret', false, $this->mockHandler);

    }

    private function errorBody(string $code = '0x0100'): string
    {

        return json_encode(['message' => 'Something went wrong.', 'code' => $code]);

    }

    public function testDeliverMessagesParsesResult()
    {

        $handler = $this->handlerReturning(
            [
                new Response(
                    200, [], json_encode(
                           [
                               'ids'   => [123, 456],
                               'usage' => [
                                   'total_cost' => 1.5,
                                   'currency'   => 'DKK',
                                   'countries'  => ['DK' => 2]
                               ]
                           ]
                       )
                )
            ]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $handler->deliverMessages(
            [
                new SMSMessage('Test message', 'Test', [new Recipient(4588888888)])
            ]
        );

        $this->assertEquals([123, 456], $result->getMessageIds());
        $this->assertEquals(2, $result->getTotalSMSCount());
        $this->assertEquals(1.5, $result->getTotalCost());
        $this->assertEquals('DKK', $result->getCurrency());

        $request = $this->mockHandler->getLastRequest();

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https://gatewayapi.com/rest/mtsms', (string)$request->getUri());

    }

    /**
     * The OAuth 1.0 signature is applied by guzzlehttp/oauth-subscriber, which ships separate major versions for
     * Guzzle 7 and Guzzle 8. This asserts the middleware is actually wired up under whichever pair is installed.
     */
    public function testRequestsAreSignedWithOauth()
    {

        $handler = $this->handlerReturning(
            [new Response(200, [], json_encode(['id' => 1, 'credit' => 12.5, 'currency' => 'DKK']))]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $handler->getCreditStatus();

        $authorization = $this->mockHandler->getLastRequest()->getHeaderLine('Authorization');

        $this->assertStringStartsWith('OAuth ', $authorization);
        $this->assertStringContainsString('oauth_consumer_key="key"', $authorization);
        $this->assertStringContainsString('oauth_signature_method="HMAC-SHA1"', $authorization);

    }

    public function testGetCreditStatusParsesAccountBalance()
    {

        $handler = $this->handlerReturning(
            [
                new Response(
                    200, [], json_encode(['id' => 1, 'credit' => 100.5, 'currency' => 'DKK'])
                )
            ]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $balance = $handler->getCreditStatus();

        $this->assertInstanceOf(AccountBalance::class, $balance);
        $this->assertEquals(100.5, $balance->getCredit());
        $this->assertEquals('DKK', $balance->getCurrency());

    }

    /**
     * The status code of a failed request determines which exception the library throws.
     */
    public static function statusCodeProvider(): array
    {

        return [
            'unauthorized'    => [401, UnauthorizedException::class, '0x0100'],
            // AlreadyCanceledOrSentException carries a fixed message and ignores the response body.
            'already handled' => [410, AlreadyCanceledOrSentException::class, null],
            'invalid message' => [422, MessageException::class, '0x0100'],
            'server error'    => [500, GatewayServerException::class, '0x0100'],
            'other error'     => [400, GatewayRequestException::class, '0x0100']
        ];

    }

    #[DataProvider('statusCodeProvider')]
    public function testErrorResponsesThrowMappedException(
        int $statusCode, string $expectedException, ?string $expectedErrorCode
    ) {

        $handler = $this->handlerReturning([new Response($statusCode, [], $this->errorBody())]);

        try {

            $handler->getCreditStatus();
            $this->fail('Expected ' . $expectedException . ' for status code ' . $statusCode . '.');

        } catch (GatewayRequestException $exception) {

            $this->assertInstanceOf($expectedException, $exception);
            $this->assertEquals($statusCode, $exception->getResponse()->getStatusCode());
            $this->assertEquals($expectedErrorCode, $exception->getGatewayAPIErrorCode());

        }

    }

    public function testInsufficientFundsErrorCodeThrowsInsufficientFundsException()
    {

        $handler = $this->handlerReturning([new Response(403, [], $this->errorBody('0x0216'))]);

        $this->expectException(InsufficientFundsException::class);

        $handler->getCreditStatus();

    }

    public function testConnectionFailureThrowsConnectionException()
    {

        $handler = $this->handlerReturning(
            [new ConnectException('Could not resolve host.', new Request('GET', 'https://gatewayapi.com/rest/me'))]
        );

        $this->expectException(ConnectionException::class);

        $handler->getCreditStatus();

    }

    public function testUnparsableSuccessfulResponseThrowsParsingException()
    {

        $handler = $this->handlerReturning([new Response(200, [], 'this is not JSON')]);

        $this->expectException(SuccessfulResponseParsingException::class);

        $handler->getCreditStatus();

    }

    public function testCancelScheduledMessagesReturnsResultPerMessageInOrder()
    {

        $handler = $this->handlerReturning(
            [
                new Response(200, [], '{}'),
                new Response(410, [], $this->errorBody('0x0300')),
                new ConnectException(
                    'Could not resolve host.', new Request('DELETE', 'https://gatewayapi.com/rest/mtsms/3')
                )
            ]
        );

        $results = $handler->cancelScheduledMessages([1, 2, 3]);

        $this->assertCount(3, $results);

        $this->assertEquals([1, 2, 3], array_map(
            function (CancelResult $result) {

                return $result->getMessageId();

            }, $results
        ));

        $this->assertEquals(CancelResult::STATUS_SUCCEEDED, $results[0]->getStatus());
        $this->assertNull($results[0]->getException());

        $this->assertEquals(CancelResult::STATUS_FAILED, $results[1]->getStatus());
        $this->assertInstanceOf(AlreadyCanceledOrSentException::class, $results[1]->getException());

        $this->assertEquals(CancelResult::STATUS_FAILED, $results[2]->getStatus());
        $this->assertInstanceOf(ConnectionException::class, $results[2]->getException());

    }

    /**
     * Only getPricesAsJSON() ever exercised the EU domain, and only over the live network.
     */
    public function testEuModeTargetsTheEuDomain()
    {

        $mock = new MockHandler(
            [new Response(200, [], json_encode(['id' => 1, 'credit' => 12.5, 'currency' => 'EUR']))]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        (new GatewayAPIHandler('key', 'secret', true, $mock))->getCreditStatus();

        $this->assertEquals('gatewayapi.eu', $mock->getLastRequest()->getUri()->getHost());

    }

    public function testDefaultModeTargetsTheComDomain()
    {

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->handlerReturning(
            [new Response(200, [], json_encode(['id' => 1, 'credit' => 12.5, 'currency' => 'DKK']))]
        )->getCreditStatus();

        $this->assertEquals('gatewayapi.com', $this->mockHandler->getLastRequest()->getUri()->getHost());

    }

    /**
     * Guzzle cannot encode the request body, so it throws before any transfer starts. This is not a transfer
     * failure and must not be reported as one; see the @throws on deliverMessages().
     */
    public function testUnencodableMessageThrowsInvalidArgumentException()
    {

        $handler = $this->handlerReturning([new Response(200, [], '{}')]);

        $this->expectException(InvalidArgumentException::class);

        $handler->deliverMessages(
            [new SMSMessage("bad \xB1\x31 utf8", 'Test', [new Recipient(4588888888)])]
        );

    }

    public function testCancelScheduledMessagesWithEmptyArrayMakesNoRequests()
    {

        $this->assertSame([], $this->handlerReturning([])->cancelScheduledMessages([]));

    }

    public function testGetPricesParsesPrices()
    {

        $mock = new MockHandler(
            [
                new Response(
                    200, [], json_encode(['standard' => [['country' => 'DK']], 'premium' => [['country' => 'SE']]])
                )
            ]
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $prices = GatewayAPIHandler::getPricesAsJSON(false, $mock);

        $this->assertEquals([['country' => 'DK']], $prices->getStandard());
        $this->assertEquals([['country' => 'SE']], $prices->getPremium());

        $this->assertEquals(
            'https://gatewayapi.com/api/prices/list/sms/json', (string)$mock->getLastRequest()->getUri()
        );

    }

    public function testGetPricesEuModeTargetsTheEuDomain()
    {

        $mock = new MockHandler([new Response(200, [], json_encode(['standard' => [], 'premium' => []]))]);

        /** @noinspection PhpUnhandledExceptionInspection */
        GatewayAPIHandler::getPricesAsJSON(true, $mock);

        $this->assertEquals(
            'https://gatewayapi.eu/api/prices/list/sms/json', (string)$mock->getLastRequest()->getUri()
        );

    }

    public function testGetPricesThrowsGatewayRequestExceptionOnErrorResponse()
    {

        $mock = new MockHandler([new Response(400, [], $this->errorBody())]);

        $this->expectException(GatewayRequestException::class);

        GatewayAPIHandler::getPricesAsJSON(false, $mock);

    }

    public function testGetPricesThrowsConnectionExceptionOnTransferFailure()
    {

        $mock = new MockHandler(
            [
                new ConnectException(
                    'Could not resolve host.',
                    new Request('GET', 'https://gatewayapi.com/api/prices/list/sms/json')
                )
            ]
        );

        $this->expectException(ConnectionException::class);

        GatewayAPIHandler::getPricesAsJSON(false, $mock);

    }

    public function testGetPricesThrowsParsingExceptionOnUnparsableResponse()
    {

        $mock = new MockHandler([new Response(200, [], 'this is not JSON')]);

        $this->expectException(SuccessfulResponseParsingException::class);

        GatewayAPIHandler::getPricesAsJSON(false, $mock);

    }
}
