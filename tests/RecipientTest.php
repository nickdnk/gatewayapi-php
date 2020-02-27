<?php


namespace nickdnk\GatewayAPI;

use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{

    public function testConstructFromArray()
    {

        $self = Recipient::constructFromArray(
            [
                'msisdn'    => 4561274239,
                'tagvalues' => ['Joe', '12']
            ]
        );

        $this->assertEquals(4561274239, $self->getMsisdn());
        $this->assertEquals(['Joe', '12'], $self->getTagValues());

    }

    public function testConstructFromInvalidArray()
    {

        $this->expectException(\InvalidArgumentException::class);

        Recipient::constructFromArray(
            [
                'msisdnn'   => 4561274239, // invalid key
                'tagvalues' => ['Joe', '12']
            ]
        );

    }
}
