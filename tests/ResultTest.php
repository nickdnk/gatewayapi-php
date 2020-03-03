<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Psr7\Response;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use nickdnk\GatewayAPI\Entities\Response\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{

    public function testConstructResponse()
    {

        $response = new Response(
            200, [], json_encode(
                   [
                       'usage' => [
                           'countries'  => [
                               'DK' => 4,
                               'SE' => 2
                           ],
                           'total_cost' => 0.00596,
                           'currency'   => 'eur'
                       ],
                       'ids'   => [1234567, 1234568]
                   ]
               )
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $result = Result::constructFromResponse($response);

        $this->assertEquals(6, $result->getTotalSMSCount());
        $this->assertEquals(0.00596, $result->getTotalCost());
        $this->assertEquals('eur', $result->getCurrency());
        $this->assertEquals([1234567, 1234568], $result->getMessageIds());
        $this->assertEquals(['DK' => 4, 'SE' => 2], $result->getCountries());

    }

    public function testConstructResponseInvalid()
    {

        $this->expectException(SuccessfulResponseParsingException::class);

        $response = new Response(
            200, [], json_encode(
                   [
                       'usage' => [
                           'countries'  => [
                               'DK' => 4,
                               'SE' => 2
                           ],
                           'total-cost' => 0.00596, // Invalid key
                           'currency'   => 'eur'
                       ],
                       'ids'   => [1234567, 1234568]
                   ]
               )
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        Result::constructFromResponse($response);

    }
}
