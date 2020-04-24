<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Psr7\Response;
use nickdnk\GatewayAPI\Entities\Response\Result;
use nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\GatewayServerException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ConstructableTest extends TestCase
{

    public function testHandleErrorResponse500ValidJSON()
    {

        $this->expectException(GatewayServerException::class);

        $response = new Response(500, [], json_encode(['error' => 'this is json']));

        /** @noinspection PhpUnhandledExceptionInspection */
        throw GatewayRequestException::constructFromResponse($response);

    }

    public function testHandleErrorResponse500InvalidJSON()
    {

        $this->expectException(GatewayServerException::class);

        $response = new Response(500, [], '<html>This is not JSON</html>');

        $exception = GatewayRequestException::constructFromResponse($response);

        $this->assertEquals(
            '<html>This is not JSON</html>',
            $exception->getResponse()
                ->getBody()
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        throw $exception;

    }

    public function testHandleErrorResponse422ValidJSON()
    {

        $this->expectException(MessageException::class);

        $response = new Response(422, [], json_encode(['error' => 'this is json']));

        /** @noinspection PhpUnhandledExceptionInspection */
        throw GatewayRequestException::constructFromResponse($response);

    }

    public function testHandleErrorResponse422InvalidJSON()
    {

        $this->expectException(GatewayRequestException::class);

        $response = new Response(422, [], '<html>This is not JSON</html>');

        $exception = GatewayRequestException::constructFromResponse($response);

        $this->assertInstanceOf(ResponseInterface::class, $exception->getResponse());
        /** @noinspection PhpUnhandledExceptionInspection */
        throw $exception;

    }

    public function testHandleInsufficientCreditException()
    {

        $this->expectException(InsufficientFundsException::class);

        $response = new Response(403, [], json_encode(['message' => 'whatever', 'code' => '0x0216']));

        /** @noinspection PhpUnhandledExceptionInspection */
        throw GatewayRequestException::constructFromResponse($response);

    }

    public function testHandleUnauthorizedValidJSON()
    {

        $this->expectException(UnauthorizedException::class);

        $response = new Response(401, [], json_encode(['message' => 'whatever', 'code' => 'whatever']));

        /** @noinspection PhpUnhandledExceptionInspection */
        throw GatewayRequestException::constructFromResponse($response);

    }

    public function testHandleUnauthorizedInvalidJSON()
    {

        $this->expectException(GatewayRequestException::class);

        $response = new Response(401, [], '<html>This is not JSON</html>');

        /** @noinspection PhpUnhandledExceptionInspection */
        throw GatewayRequestException::constructFromResponse($response);

    }

    public function testAlreadySentException()
    {

        $this->expectException(AlreadyCanceledOrSentException::class);

        $response = new Response(410);

        /** @noinspection PhpUnhandledExceptionInspection */
        throw GatewayRequestException::constructFromResponse($response);

    }

    public function testGeneric400Unknown()
    {

        $this->expectException(GatewayRequestException::class);

        $response = new Response(400, [], json_encode(['message' => 'whatever', 'code' => 'whatever']));

        $exception = GatewayRequestException::constructFromResponse($response);

        $this->assertEquals('whatever', $exception->getGatewayAPIErrorCode());
        /** @noinspection PhpUnhandledExceptionInspection */
        throw $exception;

    }

    public function testHandleEmptyResponse()
    {

        $response = new Response(204);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->assertNull(Result::constructFromResponse($response));

    }

}
