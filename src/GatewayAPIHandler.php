<?php


namespace nickdnk\GatewayAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Response\AccountBalance;
use nickdnk\GatewayAPI\Entities\CancelResult;
use nickdnk\GatewayAPI\Entities\Response\Prices;
use nickdnk\GatewayAPI\Entities\Response\Result;
use nickdnk\GatewayAPI\Entities\Request\SMSMessage;
use nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException;
use nickdnk\GatewayAPI\Exceptions\ConnectionException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\GatewayServerException;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GatewayAPIHandler
 *
 * @package nickdnk\GatewayAPI
 */
class GatewayAPIHandler
{

    private const DOMAIN_ROOT = 'https://gatewayapi.com';

    private $client;

    /**
     * Obtain a key and secret from the website. This is a prerequisite for sending SMS.
     *
     * @param string $key
     * @param string $secret
     */
    public function __construct(string $key, string $secret)
    {

        $stack = HandlerStack::create();
        $stack->push(
            new Oauth1(
                [
                    'consumer_key'    => $key,
                    'consumer_secret' => $secret
                ]
            )
        );
        $this->client = new Client(
            [
                'base_uri'                      => self::DOMAIN_ROOT,
                'handler'                       => $stack,
                RequestOptions::AUTH            => 'oauth',
                RequestOptions::CONNECT_TIMEOUT => 15,
                RequestOptions::TIMEOUT         => 60
            ]
        );

    }

    /**
     * Cancels all the messages with the provided IDs. Returns an array of results for each request in the same order
     * the message IDs were added to the input array.
     *
     * @param int[] $messageIds
     *
     * @return CancelResult[]
     */
    public function cancelScheduledMessages(array $messageIds): array
    {

        if (!$messageIds) {
            return [];
        }

        /** @var Request[] $requests */
        $requests = [];

        /** @var CancelResult[] $results */
        $results = [];

        foreach ($messageIds as $id) {

            if (!is_int($id)) {
                throw new InvalidArgumentException(
                    'Invalid message ID passed to cancelScheduledMessages. Must be an array of integers.'
                );
            }

            $requests[] = new Request('DELETE', '/rest/mtsms/' . $id);
            $results[] = new CancelResult($id);
        }

        (new Pool(
            $this->client, $requests, [
                             'concurrency' => 3,
                             'rejected'    => function (TransferException $exception, $index) use (&$results) {

                                 $results[$index]->setStatus(CancelResult::STATUS_FAILED);

                                 if ($exception instanceof RequestException) {

                                     $results[$index]->setException(
                                         GatewayRequestException::constructFromResponse($exception->getResponse())
                                     );

                                 } else {

                                     $results[$index]->setException(
                                         new ConnectionException(
                                             'Failed to connect to GatewayAPI: ' . $exception->getMessage()
                                         )
                                     );

                                 }

                             }
                         ]
        ))->promise()
            ->wait();

        return $results;

    }


    /**
     * Sends an array of SMSMessages. You can safely pass the result of json-encoded and decoded SMSMessages into
     * this function as well, such as in cases where the messages have been stored in a queue as JSON.
     *
     * For example, of these three, arrays of 1 and 3 are valid inputs:
     * 1. $messages = new SMSMessage(...);
     * 2. $json = json_encode($message);
     * 3. $decoded = json_decode($json);
     *
     * These could even be mixed: [$messages, $decoded] would work fine (assuming they are not the same message).
     *
     * @param SMSMessage[]|array $messages
     *
     * @return Result
     * @throws SuccessfulResponseParsingException
     * @throws GatewayRequestException
     * @throws GatewayServerException
     * @throws InsufficientFundsException
     * @throws ConnectionException
     * @throws MessageException
     * @throws UnauthorizedException
     */
    public function deliverMessages(array $messages): Result
    {

        return Result::constructFromResponse($this->makeRequest('POST', '/rest/mtsms', $messages));

    }

    /**
     * Returns the account as defined by credentials.
     * This shows the currency, account number and current balance of the account.
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

        return AccountBalance::constructFromResponse($this->makeRequest('GET', '/rest/me'));

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
     */
    public static function getPricesAsJSON(): Prices
    {

        try {

            return Prices::constructFromResponse(
                (new Client())->get(
                    self::DOMAIN_ROOT . '/api/prices/list/sms/json',
                    [
                        RequestOptions::CONNECT_TIMEOUT => 15,
                        RequestOptions::TIMEOUT         => 30,
                        RequestOptions::HTTP_ERRORS     => false
                    ]
                )
            );

        } catch (RequestException $exception) {

            throw GatewayRequestException::constructFromResponse($exception->getResponse());

        } catch (TransferException $exception) {

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
     * @throws AlreadyCanceledOrSentException
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

        } catch (RequestException $exception) {

            throw GatewayRequestException::constructFromResponse($exception->getResponse());

        } catch (TransferException $exception) {

            throw new ConnectionException(
                'Failed to connect to GatewayAPI: ' . $exception->getMessage()
            );

        }

    }
}
