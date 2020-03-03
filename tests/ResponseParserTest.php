<?php


namespace nickdnk\GatewayAPI;

use GuzzleHttp\Psr7\Response;
use nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\GatewayServerException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\PastSendTimeException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class ResponseParserTest extends TestCase
{

    public function testHandleErrorResponse500ValidJSON()
    {

        $response = new Response(500, [], json_encode(['error' => 'this is json']));

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(GatewayServerException::class, $exception);

    }

    public function testHandleErrorResponse500InvalidJSON()
    {

        $response = new Response(500, [], '<html>This is not JSON</html>');

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(GatewayServerException::class, $exception);

    }

    public function testHandleErrorResponse422ValidJSON()
    {

        $response = new Response(422, [], json_encode(['error' => 'this is json']));

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(MessageException::class, $exception);

    }

    public function testHandleErrorResponse422InvalidJSON()
    {

        $response = new Response(422, [], '<html>This is not JSON</html>');

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(GatewayRequestException::class, $exception);

    }

    public function testHandleInsufficientCreditException()
    {

        $response = new Response(403, [], json_encode(['message' => 'whatever', 'code' => '0x0216']));

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(InsufficientFundsException::class, $exception);

    }

    public function testHandlePastSendTimeException()
    {

        $response = new Response(403, [], json_encode(['message' => 'whatever', 'code' => '0x0308']));

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(PastSendTimeException::class, $exception);

    }

    public function testHandleUnauthorizedValidJSON()
    {

        $response = new Response(401, [], json_encode(['message' => 'whatever', 'code' => 'whatever']));

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(UnauthorizedException::class, $exception);

    }

    public function testHandleUnauthorizedInvalidJSON()
    {

        $response = new Response(401, [], '<html>This is not JSON</html>');

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(GatewayRequestException::class, $exception);

    }

    public function testAlreadySentException()
    {

        $response = new Response(410);

        $exception = ResponseParser::handleErrorResponse($response);

        $this->assertInstanceOf(AlreadyCanceledOrSentException::class, $exception);

    }

    public function testHandleEmptyResponse()
    {

        $response = new Response(204);

        $this->assertNull(ResponseParser::jsonDecodeResponse($response));

    }

}
