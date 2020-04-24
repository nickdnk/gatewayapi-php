<?php


namespace nickdnk\GatewayAPI\Tests;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Request\Recipient;
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Array passed to ' . Recipient::class . ' is missing required parameters.');

        Recipient::constructFromJSON(
            json_encode(
                [
                    'msisdnn'   => 4561274239, // invalid key
                    'tagvalues' => ['Joe', '12']
                ]
            )
        );

    }

    public function testConstructFromInvalidJSONString()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse string as valid JSON.');

        Recipient::constructFromJSON('blah not json');

    }

    public function testConstructFromNonArray()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON passed to ' . Recipient::class);

        // This is valid json and simply returns true/1
        Recipient::constructFromJSON(true);

    }

    public function testConstructFromEmptyArray()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON passed to ' . Recipient::class);

        Recipient::constructFromJSON('[]');

    }

    public function testNoCountryCode()
    {

        $this->expectException(InvalidArgumentException::class);

        $recipient = new Recipient(4588888888);
        $recipient->getCountryCode();

    }

    public function testCountryCode()
    {

        $recipient = new Recipient(4588888888, [], 'DK');
        $this->assertEquals('DK', $recipient->getCountryCode());

    }
}
