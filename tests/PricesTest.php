<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Psr7\Response;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use nickdnk\GatewayAPI\Entities\Response\Prices;
use PHPUnit\Framework\TestCase;

class PricesTest extends TestCase
{

    public function testConstructFromResponse()
    {

        $response = new Response(
            200, [], json_encode(
                   [
                       'standard' => [],
                       'premium'  => []
                   ]
               )
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $prices = Prices::constructFromResponse($response);

        $this->assertInstanceOf(Prices::class, $prices);
        $this->assertIsArray($prices->getPremium());
        $this->assertIsArray($prices->getStandard());

    }

    public function testConstructFromResponseInvalid()
    {

        $this->expectException(SuccessfulResponseParsingException::class);

        $response = new Response(
            200, [], json_encode(
                   [
                       'standard_prices' => [], // wrong
                       'premium'         => []
                   ]
               )
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        Prices::constructFromResponse($response);

    }
}
