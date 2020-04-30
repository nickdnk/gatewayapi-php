<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Psr7\Response;
use nickdnk\GatewayAPI\Entities\Response\AccountBalance;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use PHPUnit\Framework\TestCase;

class AccountBalanceTest extends TestCase
{

    public function testConstructFromResponse()
    {

        $response = new Response(
            200, [], json_encode(
                   [
                       'credit'   => 1453.55,
                       'currency' => 'eur',
                       'id'       => 1232323
                   ]
               )
        );

        /** @noinspection PhpUnhandledExceptionInspection */
        $result = AccountBalance::constructFromResponse($response);

        $this->assertEquals(1453.55, $result->getCredit());
        $this->assertEquals(1232323, $result->getId());
        $this->assertEquals('eur', $result->getCurrency());

    }

    public function testConstructFromResponseInvalid()
    {

        $this->expectException(SuccessfulResponseParsingException::class);

        $response = new Response(
            200, [], json_encode(
                   [
                       'account_credit' => 1453.55, // Invalid
                       'currency'       => 'eur',
                       'id'             => 1232323
                   ]
               )
        );

        try {

            AccountBalance::constructFromResponse($response);

        } catch (SuccessfulResponseParsingException $e) {

            $this->assertEquals($response, $e->getResponse());
            throw $e;

        }

    }
}
