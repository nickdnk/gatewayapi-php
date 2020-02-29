<?php


namespace nickdnk\GatewayAPI;

use PHPUnit\Framework\TestCase;

class RecipientTest extends TestCase
{

    public function testConstructFromJSON()
    {

        $self = Recipient::constructFromJSON(
            json_encode(
                new Recipient(4561274239, ['Joe', '12'])
            )
        );

        $this->assertEquals(4561274239, $self->getMsisdn());
        $this->assertEquals(['Joe', '12'], $self->getTagValues());

    }

    public function testConstructFromInvalidArray()
    {

        $this->expectException(\InvalidArgumentException::class);

        Recipient::constructFromJSON(
            json_encode(
                [
                    'msisdnn'   => 4561274239, // invalid key
                    'tagvalues' => ['Joe', '12']
                ]
            )
        );

    }
}
