<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use nickdnk\GatewayAPI\Entities\Response\AccountBalance;
use nickdnk\GatewayAPI\Entities\Response\Prices;
use nickdnk\GatewayAPI\Entities\Response\Result;
use nickdnk\GatewayAPI\Entities\Request\SMSMessage;
use nickdnk\GatewayAPI\Exceptions\BaseException;
use nickdnk\GatewayAPI\Exceptions\ConnectionException;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\MessageDeliveryException;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GatewayAPIHandler
 *
 * Talks to the new Mobile Messaging API (https://messaging.gatewayapi.com).
 * Account/credit and price look-ups still use the legacy REST endpoints on
 * gatewayapi.com, as the messaging API does not expose equivalents.
 *
 * @package nickdnk\GatewayAPI
 */
class GatewayAPIHandler
{

    private const DOMAIN_ROOT_COM = 'https://gatewayapi.com';
    private const DOMAIN_ROOT_EU  = 'https://gatewayapi.eu';

    private const MESSAGING_ROOT_COM = 'https://messaging.gatewayapi.com';
    private const MESSAGING_ROOT_EU  = 'https://messaging.gatewayapi.eu';

    /** The messaging API accepts at most this many messages per `/mobile/multi` request. */
    private const MAX_BATCH_SIZE = 1000;

    /** Number of `/mobile/multi` batches sent concurrently. */
    private const POOL_CONCURRENCY = 5;

    private Client $client;

    /** Legacy REST root used for account/credit look-ups. */
    private string $legacyRoot;

    /**
     * Obtain a key and secret from the website. This is a prerequisite for sending SMS.
     * Pass `true` to `$euMode` to use the EU-only setup.
     *
     * The same OAuth1 credentials authenticate both the messaging API and the
     * legacy REST endpoints, so the setup is unchanged from previous versions.
     *
     * @param string        $key
     * @param string        $secret
     * @param bool          $euMode
     * @param callable|null $handler Optional Guzzle handler, primarily for testing.
     */
    public function __construct(string $key, string $secret, bool $euMode = false, ?callable $handler = null)
    {

        $stack = HandlerStack::create($handler);
        $stack->push(
            new Oauth1(
                [
                    'consumer_key'    => $key,
                    'consumer_secret' => $secret,
                    'token'           => '',
                    'token_secret'    => ''
                ]
            )
        );

        $this->legacyRoot = $euMode ? self::DOMAIN_ROOT_EU : self::DOMAIN_ROOT_COM;

        $this->client = new Client(
            [
                'base_uri'                      => $euMode ? self::MESSAGING_ROOT_EU : self::MESSAGING_ROOT_COM,
                'handler'                       => $stack,
                RequestOptions::AUTH            => 'oauth',
                RequestOptions::CONNECT_TIMEOUT => 15,
                RequestOptions::TIMEOUT         => 60
            ]
        );

    }

    /**
     * Sends an array of SMSMessages using the messaging API's `/mobile/multi` endpoint.
     *
     * Each SMSMessage is expanded into one message per recipient, as the messaging API
     * accepts a single recipient per message. The resulting messages are split into
     * batches of at most {@see GatewayAPIHandler::MAX_BATCH_SIZE} and delivered
     * concurrently through a Guzzle pool. The returned Result aggregates the message IDs
     * from every batch, in submission order.
     *
     * You can safely pass the result of json-encoded and decoded SMSMessages into this
     * function as well, such as in cases where the messages have been stored in a queue
     * as JSON.
     *
     * All batches are attempted regardless of individual failures. If any batch fails, a
     * {@see MessageDeliveryException} is thrown after the pool settles, carrying one
     * exception per failed batch and a partial Result of the IDs that were accepted.
     * Each failure is only kept as its (small) error response, so memory stays bounded by
     * the number of batches even when every batch fails.
     *
     * You can safely pass the result of json-encoded and decoded SMSMessages into this
     * function as well, such as in cases where the messages have been stored in a queue
     * as JSON.
     *
     * @param SMSMessage[]|array $messages
     *
     * @return Result
     * @throws MessageDeliveryException If one or more batches fail to deliver.
     * @throws SuccessfulResponseParsingException If a successful batch response cannot be parsed.
     * @throws \InvalidArgumentException If a passed (decoded) message is missing required fields.
     */
    public function deliverMessages(array $messages): Result
    {

        /** @var ResponseInterface[] $responses */
        $responses = [];

        /** @var BaseException[] $errors */
        $errors = [];

        (new Pool(
            $this->client, $this->buildBatchRequests($messages), [
                             'concurrency' => self::POOL_CONCURRENCY,
                             'fulfilled'   => function (ResponseInterface $response, int $index) use (&$responses) {

                                 $responses[$index] = $response;

                             },
                             'rejected'    => function (\Throwable $exception, int $index) use (&$errors) {

                                 // Convert every rejection into one of our own exception types so a
                                 // GuzzleException never escapes deliverMessages.
                                 if ($exception instanceof BadResponseException) {

                                     // Store only the (small) error response, not the request,
                                     // so memory stays bounded even if every batch fails.
                                     $errors[$index] = GatewayRequestException::constructFromResponse(
                                         $exception->getResponse()
                                     );

                                 } else {

                                     $errors[$index] = new ConnectionException(
                                         'Failed to connect to GatewayAPI: ' . $exception->getMessage()
                                     );

                                 }

                             }
                         ]
        ))->promise()
            ->wait();

        // Preserve submission order across batches before merging the IDs.
        ksort($responses);

        $messageIds = [];

        foreach ($responses as $response) {
            foreach (Result::constructFromResponse($response)->getMessageIds() as $messageId) {
                $messageIds[] = $messageId;
            }
        }

        $result = new Result(0.0, count($messageIds), '', [], $messageIds);

        if ($errors) {

            ksort($errors);

            throw new MessageDeliveryException(
                count($errors) . ' of ' . (count($errors) + count($responses)) . ' message batches failed to deliver.',
                array_values($errors),
                $result
            );

        }

        return $result;

    }

    /**
     * Lazily yields `/mobile/multi` requests, each carrying at most
     * {@see GatewayAPIHandler::MAX_BATCH_SIZE} messages. Messages are expanded one per
     * recipient. Using a generator keeps memory bounded to a single batch at a time,
     * regardless of how many recipients are being sent to, as the Guzzle pool only pulls
     * requests as concurrency slots free up.
     *
     * @param SMSMessage[]|array $messages
     *
     * @return \Generator<int, Request>
     * @throws \InvalidArgumentException If a passed (decoded) message is missing required fields.
     */
    private function buildBatchRequests(array $messages): \Generator
    {

        $batch = [];

        foreach ($messages as $message) {

            if (!($message instanceof SMSMessage)) {
                // Allow decoded JSON (arrays or stdClass) to be passed straight in,
                // matching the legacy behaviour where queued messages are re-hydrated.
                $message = SMSMessage::constructFromArray(json_decode(json_encode($message), true));
            }

            foreach ($message->toMobileMessageRequests() as $request) {

                $batch[] = $request;

                if (count($batch) === self::MAX_BATCH_SIZE) {
                    yield $this->buildMultiRequest($batch);
                    $batch = [];
                }

            }

        }

        if ($batch) {
            yield $this->buildMultiRequest($batch);
        }

    }

    /**
     * @param array[] $batch
     */
    private function buildMultiRequest(array $batch): Request
    {

        return new Request(
            'POST',
            '/mobile/multi',
            ['Content-Type' => 'application/json'],
            json_encode(['messages' => $batch])
        );

    }

    /**
     * Returns the account as defined by credentials.
     * This shows the currency, account number and current balance of the account.
     *
     * Uses the legacy REST endpoint, as the messaging API has no equivalent.
     *
     * @return AccountBalance
     * @throws ConnectionException
     * @throws GatewayRequestException
     * @throws GatewayServerException
     * @throws InsufficientFundsException
     * @throws MessageException
     * @throws SuccessfulResponseParsingException
     * @throws UnauthorizedException
     */
    public function getCreditStatus(): AccountBalance
    {

        return AccountBalance::constructFromResponse(
            $this->makeRequest('GET', $this->legacyRoot . '/rest/me')
        );

    }

    /**
     * Returns the prices as JSON. This is a public endpoint you can browse to at any time.
     * This is a convenience method that ensures proper parsing and handling of this endpoint.
     *
     * @link https://gatewayapi.com/api/prices/list/sms/json
     *
     * @return Prices
     * @throws ConnectionException
     * @throws GatewayRequestException
     * @throws SuccessfulResponseParsingException If the price list cannot be parsed.
     */
    public static function getPricesAsJSON(bool $euMode = false): Prices
    {

        try {

            return Prices::constructFromResponse(
                (new Client())->get(
                    ($euMode ? self::DOMAIN_ROOT_EU : self::DOMAIN_ROOT_COM) . '/api/prices/list/sms/json',
                    [
                        RequestOptions::CONNECT_TIMEOUT => 15,
                        RequestOptions::TIMEOUT         => 30
                    ]
                )
            );

        } catch (BadResponseException $exception) {

            throw GatewayRequestException::constructFromResponse($exception->getResponse());

        } catch (GuzzleException $exception) {

            // Catch the GuzzleException interface (not just TransferException) so no
            // Guzzle exception ever escapes this method.
            throw new ConnectionException(
                'Failed to connect to GatewayAPI to fetch prices: ' . $exception->getMessage()
            );

        }

    }


    /**
     * @param string     $method
     * @param string     $endPoint
     * @param array|null $body
     *
     * @return ResponseInterface
     * @throws ConnectionException
     * @throws GatewayRequestException
     * @throws GatewayServerException
     * @throws InsufficientFundsException
     * @throws MessageException
     * @throws UnauthorizedException
     */
    private function makeRequest(string $method, string $endPoint, ?array $body = null): ResponseInterface
    {

        try {

            return $this->client->request(
                $method,
                $endPoint,
                $body !== null ? [RequestOptions::JSON => $body] : []
            );

        } catch (BadResponseException $exception) {

            throw GatewayRequestException::constructFromResponse($exception->getResponse());

        } catch (GuzzleException $exception) {

            // Catch the GuzzleException interface (not just TransferException) so no
            // Guzzle exception ever escapes this method.
            throw new ConnectionException(
                'Failed to connect to GatewayAPI: ' . $exception->getMessage()
            );

        }

    }
}
