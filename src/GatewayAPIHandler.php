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
use nickdnk\GatewayAPI\Exceptions\BaseException;
use nickdnk\GatewayAPI\Exceptions\ConnectionException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\PastSendTimeException;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\GatewayServerException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use stdClass;

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
                throw new \InvalidArgumentException(
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
     * The second parameter determines if the library should automatically remove invalid sendtime parameters
     * and retry the request when encountering this error.
     *
     * @param SMSMessage[] $SMSMessages
     * @param bool         $allowSendTimeAdjustment
     *
     * @return Result
     * @throws BaseException
     * @throws GatewayRequestException
     * @throws GatewayServerException
     * @throws InsufficientFundsException
     * @throws PastSendTimeException
     * @throws ConnectionException
     * @throws MessageException
     * @throws UnauthorizedException
     */
    public function deliverMessages(array $SMSMessages, bool $allowSendTimeAdjustment = true): Result
    {

        try {

            $json = $this->makeRequest('POST', '/rest/mtsms', $SMSMessages);

            if (isset($json['usage']) && isset($json['ids'])) {

                $smsCount = 0;

                foreach ($json['usage']['countries'] as $country => $count) {

                    $smsCount += $count;

                }

                return new Result(
                    $json['usage']['total_cost'],
                    $smsCount,
                    $json['usage']['currency'],
                    $json['usage']['countries'],
                    $json['ids']
                );

            }

            throw new BaseException(
                'Missing expected key/values from GatewayAPI response. Received: ' . json_encode($json), null, null
            );

        } catch (PastSendTimeException $exception) {

            if (!$allowSendTimeAdjustment) {
                throw $exception;
            }

            /**
             * This error means the 'sendtime' parameter is in the past. This can happen if you queue
             * the SMS message and there's a processing delay that causes the message to be delivered
             * to GatewayAPI after the intended send time. We recover from this by removing this
             * parameter and calling this method recursively.
             */
            foreach ($SMSMessages as &$aMessage) {

                /**
                 * In cases where the jobs are parsed to JSON (such as if processed via a queue),
                 * the input to this method may be raw stdClass objects, and in that case we use
                 * unset() instead of removeSendTime().
                 */
                if ($aMessage instanceof SMSMessage) {

                    $aMessage->removeSendTime();

                } else {

                    if ($aMessage instanceof stdClass) {

                        unset($aMessage->sendtime);

                    } else {

                        if (isset($aMessage['sendtime'])) {

                            // If a JSON-parsed job was passed into this method using associative arrays
                            unset($aMessage['sendtime']);

                        } else {

                            // Don't keep looping if we cannot recover.
                            throw $exception;
                        }
                    }
                }
            }
            unset($aMessage);

            return $this->deliverMessages($SMSMessages, false);

        }

    }

    /**
     * @return AccountBalance
     * @throws UnauthorizedException
     * @throws GatewayRequestException|BaseException|ConnectionException
     */
    public function getCreditStatus(): AccountBalance
    {

        $json = $this->makeRequest('GET', '/rest/me');

        if (isset($json['credit']) && isset($json['currency']) && isset($json['id'])) {

            return new AccountBalance($json['credit'], $json['currency'], $json['id']);

        }

        throw new BaseException('Missing expected key/values from GatewayAPI response.', null, null);
    }

    /**
     *
     * Returns the prices as JSON. This is a public endpoint you can browse to at any time.
     * This is a convenience method that ensures proper parsing and handling of this endpoint.
     * The return value is an associative array matching the raw response of this link.
     *
     * @link https://gatewayapi.com/api/prices/list/sms/json
     *
     * @return array
     * @throws BaseException
     * @throws ConnectionException
     */
    public static function getPricesAsJSON(): array
    {

        try {

            $response = (new Client())->get(
                'https://gatewayapi.com/api/prices/list/sms/json',
                [
                    'connect_timeout' => 15,
                    'timeout'         => 30
                ]
            );

            $json = json_decode($response->getBody(), true);

            if ($json) {

                if (isset($json['standard'])
                    && isset($json['premium'])
                    && is_array($json['standard'])
                    && is_array($json['premium'])) {

                    return $json;

                }

            }

            throw new BaseException(
                'Missing expected key/values from GatewayAPI price response: ' . json_encode($json), null, $response
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
     * @return array|string|null
     * @throws BaseException
     * @throws InsufficientFundsException
     * @throws MessageException
     * @throws PastSendTimeException
     * @throws ConnectionException
     * @throws UnauthorizedException
     */
    private function makeRequest(string $method, string $endPoint, ?array $body = null)
    {

        try {

            $parameters = [
                "connect_timeout" => 15,
                "timeout"         => 60
            ];

            if ($body !== null) {
                $parameters['json'] = $body;
            }

            $response = $this->client->request(
                $method,
                $endPoint,
                $parameters
            );

            return ResponseParser::jsonDecodeResponse($response);

        } catch (RequestException $exception) {

            throw ResponseParser::handleErrorResponse($exception->getResponse());

        } catch (TransferException $exception) {

            throw new ConnectionException(
                'Failed to connect to GatewayAPI: ' . $exception->getMessage()
            );

        }

    }
}