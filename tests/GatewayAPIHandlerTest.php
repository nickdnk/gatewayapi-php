<?php


namespace nickdnk\GatewayAPI\Tests;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\CancelResult;
use nickdnk\GatewayAPI\Entities\Response\AccountBalance;
use nickdnk\GatewayAPI\Entities\Response\Prices;
use nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use nickdnk\GatewayAPI\GatewayAPIHandler;
use nickdnk\GatewayAPI\Entities\Request\Recipient;
use nickdnk\GatewayAPI\Entities\Request\SMSMessage;
use PHPUnit\Framework\TestCase;

class GatewayAPIHandlerTest extends TestCase
{

    // Replace these with your own key, secret and phone number to run this test. It will cost you 1 SMS per test run.
    // Pass `true` in `setUp` below to test against the EU-only configuration.
    private const TEST_KEY    = '';
    private const TEST_SECRET = '';
    private const TEST_NUMBER = 4588888888;

    private $handler;

    protected function setUp(): void
    {

        $this->handler = new GatewayAPIHandler(
            self::TEST_KEY, self::TEST_SECRET, false
        );
    }

    public function testSendInvalidSMS()
    {

        if (!self::TEST_SECRET || !self::TEST_KEY) {
            $this->markTestSkipped('Key and secret missing.');
        }

        $this->expectException(MessageException::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->handler->deliverMessages(
            [
                new SMSMessage(
                    'Test message to %NAME% and %AGE%', 'Test', [
                    new Recipient(self::TEST_NUMBER, ['Joe', 34]) // All tag values must be strings
                ], null, ['%NAME%', '%AGE%'], time() + 3600
                )
            ]
        );

    }

    public function testSendAndCancelSMS()
    {

        if (!self::TEST_SECRET || !self::TEST_KEY) {
            $this->markTestSkipped('Key and secret missing.');
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $result = $this->handler->deliverMessages(
            [
                new SMSMessage(
                    'Test message', 'Test', [
                    new Recipient(self::TEST_NUMBER)
                ], null, [], time() + 3600
                ),
                new SMSMessage(
                    'Test message', 'Test', [
                                      new Recipient(self::TEST_NUMBER)
                                  ]
                )
            ]
        );

        $this->assertEquals(2, $result->getTotalSMSCount());

        foreach ($result->getMessageIds() as $messageId) {

            $this->assertIsInt($messageId);

        }

        $cancelResults = $this->handler->cancelScheduledMessages($result->getMessageIds());

        $this->assertEquals(CancelResult::STATUS_SUCCEEDED, $cancelResults[0]->getStatus());
        $this->assertNull($cancelResults[0]->getException());

        $this->assertEquals(CancelResult::STATUS_FAILED, $cancelResults[1]->getStatus());
        $this->assertInstanceOf(AlreadyCanceledOrSentException::class, $cancelResults[1]->getException());

        $this->assertEquals($result->getMessageIds()[0], $cancelResults[0]->getMessageId());
        $this->assertEquals($result->getMessageIds()[1], $cancelResults[1]->getMessageId());

    }

    public function testGetAccountBalance()
    {

        if (!self::TEST_SECRET || !self::TEST_KEY) {
            $this->markTestSkipped('Key and secret missing.');
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $account = $this->handler->getCreditStatus();

        $this->assertInstanceOf(AccountBalance::class, $account);

    }

    public function testCancelInvalidIDs()
    {

        $this->expectException(InvalidArgumentException::class);

        $this->handler->cancelScheduledMessages(['not an integer']);

    }

    public function testInvalidCredentials()
    {

        $this->expectException(UnauthorizedException::class);

        // replace credentials
        $this->handler = new GatewayAPIHandler('invalid', 'invalid');

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->handler->deliverMessages(
            [
                new SMSMessage(
                    'Test message', 'Test', [
                                      new Recipient(self::TEST_NUMBER)
                                  ]
                )
            ]
        );

    }

    public function testGetPrices()
    {

        /** @noinspection PhpUnhandledExceptionInspection */
        $prices = GatewayAPIHandler::getPricesAsJSON();

        $this->assertInstanceOf(Prices::class, $prices);

    }

    public function testGetPricesEu()
    {

        /** @noinspection PhpUnhandledExceptionInspection */
        $prices = GatewayAPIHandler::getPricesAsJSON(true);

        $this->assertInstanceOf(Prices::class, $prices);

    }
}
