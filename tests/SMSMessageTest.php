<?php


namespace nickdnk\GatewayAPI;

use PHPUnit\Framework\TestCase;

class SMSMessageTest extends TestCase
{

    private const validArray
        = [
            'class'      => 'secret',
            'message'    => 'Hello! This is a message to %NAME% aged %AGE%!',
            'sender'     => 'Apple',
            'recipients' => [
                [
                    'msisdn'    => 4561232323,
                    'tagvalues' => ['Joe', '22']
                ],
                [
                    'msisdn'    => 4577364722,
                    'tagvalues' => ['Mark', '23']
                ]
            ],
            'tags'       => ['%NAME%', '%AGE%'],
            'sendtime'   => 1585835858,
            'userref'    => 'reference text'
        ];

    public function testConstructFromArray()
    {

        $self = SMSMessage::constructFromArray(
            self::validArray
        );

        $this->assertEquals('secret', $self->getClass());
        $this->assertEquals('Hello! This is a message to %NAME% aged %AGE%!', $self->getMessage());
        $this->assertIsArray($self->getRecipients());

        $this->assertEquals(4561232323, $self->getRecipients()[0]->getMsisdn());
        $this->assertEquals(['Joe', '22'], $self->getRecipients()[0]->getTagValues());

        $this->assertEquals(4577364722, $self->getRecipients()[1]->getMsisdn());
        $this->assertEquals(['Mark', '23'], $self->getRecipients()[1]->getTagValues());
        $this->assertEquals('reference text', $self->getUserref());
        $this->assertEquals(1585835858, $self->getSendtime());

    }

    public function testConstructFromDictionary()
    {

        // json_encode and decode to stdClass()
        $self = SMSMessage::constructFromArray(
            json_decode(json_encode(self::validArray))
        );

        $this->assertEquals('secret', $self->getClass());
        $this->assertEquals('Hello! This is a message to %NAME% aged %AGE%!', $self->getMessage());
        $this->assertIsArray($self->getRecipients());

        $this->assertEquals(4561232323, $self->getRecipients()[0]->getMsisdn());
        $this->assertEquals(['Joe', '22'], $self->getRecipients()[0]->getTagValues());

        $this->assertEquals(4577364722, $self->getRecipients()[1]->getMsisdn());
        $this->assertEquals(['Mark', '23'], $self->getRecipients()[1]->getTagValues());
        $this->assertEquals('reference text', $self->getUserref());
        $this->assertEquals(1585835858, $self->getSendtime());

    }

    public function testConstructFromInvalidArray()
    {

        $this->expectException(\InvalidArgumentException::class);

        SMSMessage::constructFromArray(
            [
                'class'      => 'secret',
                'msg'        => 'Hello! This is a message to %NAME% aged %AGE%!', // invalid key
                'sender'     => 'Apple',
                'recipients' => [
                    [
                        'msisdn'    => 4561232323,
                        'tagvalues' => ['Joe', '22']
                    ],
                    [
                        'msisdn'    => 4577364722,
                        'tagvalues' => ['Mark', '23']
                    ]
                ],
                'tags'       => ['%NAME%', '%AGE%'],
                'sendtime'   => 1585835858
            ]
        );

    }
}
