<?php


namespace nickdnk\GatewayAPI\Tests;

use GuzzleHttp\Psr7\Response;
use nickdnk\GatewayAPI\Entities\CancelResult;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use PHPUnit\Framework\TestCase;

class CancelResultTest extends TestCase
{

    public function testCancelResult()
    {

        $cancelResult = new CancelResult(10);
        $this->assertEquals(10, $cancelResult->getMessageId());
        $this->assertEquals(CancelResult::STATUS_SUCCEEDED, $cancelResult->getStatus());

        $cancelResult->setStatus(CancelResult::STATUS_FAILED);
        $this->assertEquals(CancelResult::STATUS_FAILED, $cancelResult->getStatus());

        $exception = new GatewayRequestException('test', null);
        $exception->setResponse(new Response(400));
        $cancelResult->setException($exception);
        $this->assertEquals($exception, $cancelResult->getException());

    }

}
