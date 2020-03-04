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
use nickdnk\GatewayAPI\Exceptions\BaseException;
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

    private $client;

    /**
     * GatewayAPIHandler constructor.
     *
     * Obtain a key and secret from the website.
     * Save them as env-variables and pass them into your constructor when you need to send SMS messages.
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
                    'consumer_secret' => $secret,
                    'token'           => '',
                    'token_secret'    => ''
                ]
            )
        );
        $this->client = new Client(
            [
                'base_uri'           => 'https://gatewayapi.com',
                'handler'            => $stack,
                RequestOptions::AUTH => 'oauth'
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
                             'rejected'    => function (TransferException $reason, $index) use (&$results) {

                                 $results[$index]->setStatus(CancelResult::STATUS_FAILED);

                                 if ($reason instanceof RequestException) {

                                     $results[$index]->setException(
                                         ResponseParser::handleErrorResponse(
                                             $reason->getResponse()
                                         )
                                     );

                                 } else {

                                     $results[$index]->setException(
                                         new ConnectionException(
                                             'Failed to connect to GatewayAPI: ' . $reason->getMessage()
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
     * Sends an array of SMSMessages - either as their class or a regular PHP array (json_decoded).
     *
     * @param SMSMessage[] $messages
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
     * @return AccountBalance
     * @throws UnauthorizedException
     * @throws GatewayRequestException|BaseException|ConnectionException|SuccessfulResponseParsingException
     */
    public function getCreditStatus(): AccountBalance
    {

        return AccountBalance::constructFromResponse($this->makeRequest('GET', '/rest/me'));

    }

    /**
     *
     * Returns the prices as JSON. This is a public endpoint you can browse to at any time.
     * This is a convenience method that ensures proper parsing and handling of this endpoint.
     * The return value is an associative array matching the raw response of this link.
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
                    'https://gatewayapi.com/api/prices/list/sms/json',
                    [
                        'connect_timeout' => 15,
                        'timeout'         => 30
                    ]
                )
            );

        } catch (RequestException $exception) {

            throw ResponseParser::handleErrorResponse($exception->getResponse());

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
     * @throws ConnectionException
     * @throws Exceptions\AlreadyCanceledOrSentException
     * @throws GatewayRequestException
     * @throws GatewayServerException
     * @throws InsufficientFundsException
     * @throws MessageException
     * @throws UnauthorizedException
     */
    private function makeRequest(string $method, string $endPoint, ?array $body = null): ResponseInterface
    {

        try {

            $parameters = [
                "connect_timeout" => 15,
                "timeout"         => 60
            ];

            if ($body !== null) {
                $parameters['json'] = $body;
            }

            return $this->client->request(
                $method,
                $endPoint,
                $parameters
            );

        } catch (RequestException $exception) {

            throw ResponseParser::handleErrorResponse($exception->getResponse());

        } catch (TransferException $exception) {

            throw new ConnectionException(
                'Failed to connect to GatewayAPI: ' . $exception->getMessage()
            );

        }

    }
}
