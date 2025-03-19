<?php


namespace nickdnk\GatewayAPI\Tests;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Request\Recipient;
use nickdnk\GatewayAPI\Entities\Request\SMSMessage;
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
                ], 'reference text', ['%NAME%', '%AGE%'], 1585835858, SMSMessage::CLASS_SECRET,
                    'https://example.com/callback', SMSMessage::ENCODING_UCS2
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
        $this->assertEquals('reference text', $self->getUserReference());
        $this->assertEquals(1585835858, $self->getSendtime());

        $this->assertEquals('https://example.com/callback', $self->getCallbackUrl());
        $this->assertEquals('UCS2', $self->getEncoding());

    }

    public function testConstructFromInvalidArray()
    {

        $this->expectException(InvalidArgumentException::class);

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

    public function testConstructFromInvalidJSON()
    {

        $this->expectException(InvalidArgumentException::class);

        SMSMessage::constructFromJSON('blah');

    }

    public function testInvalidClass()
    {

        $this->expectException(InvalidArgumentException::class);

        $message = new SMSMessage('Test', 'test');
        $message->setClass('something invalid');

    }

    public function testSendTime()
    {

        $sendTime = time();

        $message = new SMSMessage('test', 'sender');

        $message->setSendTime($sendTime);

        $this->assertEquals($sendTime, $message->getSendtime());

        $message->removeSendTime();

        $this->assertNull($message->getSendtime());
    }

    public function testAddRecipient()
    {

        $message = new SMSMessage('test', 'sender');

        $recipient = new Recipient(4588888888);

        $message->addRecipient($recipient);

        $this->assertEquals([$recipient], $message->getRecipients());

    }

    public function testUserReference()
    {

        $message = new SMSMessage('test', 'sender');
        $message->setUserReference('a reference');

        $this->assertEquals('a reference', $message->getUserReference());

    }

    public function testSender()
    {

        $message = new SMSMessage('test', 'sender');

        $this->assertEquals('sender', $message->getSender());

    }

    public function testTags()
    {

        $message = new SMSMessage('test', 'sender');
        $message->setTags(['test tag']);

        $this->assertEquals(['test tag'], $message->getTags());

    }

    public function testRecipients()
    {

        $recipients = [new Recipient(4588888888)];

        $message = new SMSMessage('test', 'sender');
        $message->setRecipients($recipients);

        $this->assertEquals($recipients, $message->getRecipients());

    }

    public function testCallbackUrl()
    {

        $message = new SMSMessage('test', 'sender');
        $message->setCallbackUrl('https://example.com/callback');

        $this->assertEquals('https://example.com/callback', $message->getCallbackUrl());
    }

    public function testEncodingUCS2()
    {

        $message = new SMSMessage('test', 'sender');
        $message->setEncoding(SMSMessage::ENCODING_UCS2);

        $this->assertEquals('UCS2', $message->getEncoding());
    }

    public function testEncodingUTF8()
    {

        $message = new SMSMessage('test', 'sender');
        $message->setEncoding(SMSMessage::ENCODING_UTF8);

        $this->assertEquals('UTF8', $message->getEncoding());
    }

    public function testEncodingNull()
    {

        $message = new SMSMessage('test', 'sender');
        $message->setEncoding(SMSMessage::ENCODING_UTF8);
        $message->setEncoding(null);

        $this->assertNull($message->getEncoding());
    }
}
