<?php


namespace nickdnk\GatewayAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException;
use nickdnk\GatewayAPI\Exceptions\BaseException;
use nickdnk\GatewayAPI\Exceptions\ConnectionException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\PastSendTimeException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
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
     * Cancels all the messages with the provided IDs. If multiple errors occur only the first response will be
     * thrown as an exception. The rest will either fail or succeed silently.
     *
     * @param array $messageIds
     *
     * @return void
     * @throws UnauthorizedException
     * @throws AlreadyCanceledOrSentException
     * @throws ConnectionException
     * @throws BaseException
     */
    public function cancelScheduledMessages(array $messageIds): void
    {

        if (!$messageIds) {
            return;
        }

        /** @var BaseException|UnauthorizedException|AlreadyCanceledOrSentException|ConnectionException $exception */
        $exception = null;

        $requests = [];

        foreach ($messageIds as $id) {

            $requests[] = new Request('DELETE', '/rest/mtsms/' . $id);

        }

        (new Pool(
            $this->client, $requests, [
                             'concurrency' => 3,
                             'fulfilled'   => function (ResponseInterface $response, $index) {

                                 // Great success.

                             },
                             'rejected'    => function (GuzzleException $reason, $index) use (&$exception, $messageIds
                             ) {

                                 if (!$exception) {

                                     if ($reason instanceof RequestException) {

                                         $exception = $this->handleErrorResponse(
                                             $reason->getResponse(),
                                             $messageIds[$index]
                                         );

                                     } else {

                                         $exception = new ConnectionException(
                                             'Failed to connect to GatewayAPI: ' . $reason->getMessage()
                                         );

                                     }

                                 }

                             },
                         ]
        ))->promise()
            ->wait();

        if ($exception) {

            throw $exception;

        }

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

            throw new BaseException('Missing expected key/values from GatewayAPI response.', null, null);

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
                            throw new BaseException(
                                'Failed to handle invalid \'sendtime\' parameter.',
                                $exception->getGatewayAPIErrorCode(),
                                $exception->getResponse()
                            );
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
     * @throws BaseException|ConnectionException
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
     * @throws ConnectionException|BaseException
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

            throw new BaseException('Missing expected key/values from GatewayAPI response.', null, null);

        } catch (RequestException $exception) {

            throw new BaseException(
                'Failed to fetch GatewayAPI prices: ' . $exception->getMessage(), null, $exception->getResponse()
            );

        } catch (TransferException $exception) {

            throw new ConnectionException(
                'Failed to connect to GatewayAPI to fetch prices: ' . $exception->getMessage()
            );

        }

    }


    /**
     * @param ResponseInterface $response
     * @param int|null          $resourceId
     *
     * @return BaseException|AlreadyCanceledOrSentException|InsufficientFundsException|MessageException|PastSendTimeException|UnauthorizedException|ConnectionException
     */
    private function handleErrorResponse(ResponseInterface $response, ?int $resourceId = null)
    {

        if ($response->getStatusCode() === 410) {

            return new AlreadyCanceledOrSentException(
                $resourceId, $response
            );

        }

        $json = json_decode($response->getBody(), true);

        if (!$json) {
            return new BaseException('Failed to parse error response from GatewayAPI.', null, $response);
        }

        $message = isset($json['message']) ? $json['message'] : null;
        $code = isset($json['code']) ? $json['code'] : null;

        if ($code === '0x0216') {
            return new InsufficientFundsException($code, $response);
        }

        if ($code === '0x0308') {
            return new PastSendTimeException($code, $response);
        }

        if ($response->getStatusCode() === 401) {

            return new UnauthorizedException(
                $message, $code, $response
            );

        }

        if ($response->getStatusCode() === 422) {

            return new MessageException(
                $message, $code, $response
            );

        }

        return new BaseException(
            $message, $code, $response
        );

    }

    /**
     * @param string     $method
     * @param string     $endPoint
     * @param array|null $body
     *
     * @return Result
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

            if ($response->getStatusCode() === 204) {
                return null;
            }

            $json = json_decode($response->getBody(), true);

            if (!$json) {
                throw new BaseException(
                    'Failed to parse successful response from GatewayAPI as JSON.', null, $response
                );
            }

            return $json;

        } catch (RequestException $exception) {

            throw $this->handleErrorResponse($exception->getResponse());

        } catch (TransferException $exception) {

            throw new ConnectionException(
                'Failed to connect to GatewayAPI: ' . $exception->getMessage()
            );

        }

    }
}