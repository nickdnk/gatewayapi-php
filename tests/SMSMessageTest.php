<?php


namespace nickdnk\GatewayAPI;

use PHPUnit\Framework\TestCase;

class SMSMessageTest extends TestCase
{

    public function testConstructFromJSON()
    {

        $self = SMSMessage::constructFromJSON(
            json_encode(
                new SMSMessage(
                    'Hello! This is a message to %NAME% aged %AGE%!', 'Apple', [
                    new Recipient(4561232323, ['Joe', '22']),
                    new Recipient(4577364722, ['Mark', '23'])
                ], 'reference text', ['%NAME%', '%AGE%'], 1585835858, SMSMessage::CLASS_SECRET
                )
            )
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

        SMSMessage::constructFromJSON(
            json_encode(
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
            )
        );

    }
}
