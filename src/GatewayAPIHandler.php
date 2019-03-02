<?php


namespace nickdnk\GatewayAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use nickdnk\GatewayAPI\Exceptions\BaseException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;

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

        $stack = \GuzzleHttp\HandlerStack::create();
        $stack->push(
            new \GuzzleHttp\Subscriber\Oauth\Oauth1(
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
                'base_uri'    => 'https://gatewayapi.com',
                'handler'     => $stack,
                'auth'        => 'oauth',
                'http_errors' => false
            ]
        );

    }

    /**
     * @param SMSMessage[] $SMSMessages
     *
     * @return Result
     * @throws BaseException
     * @throws InsufficientFundsException
     * @throws MessageException
     * @throws UnauthorizedException
     */
    public function deliverMessages(array $SMSMessages): Result
    {

        try {

            $response = $this->client->post(
                '/rest/mtsms',
                [
                    'json'            => $SMSMessages,
                    "connect_timeout" => 15,
                    "timeout"         => 60
                ]
            );

            $json = json_decode($response->getBody(), true);

            if ($json) {

                if ($response->getStatusCode() === 200) {

                    if (isset($json['usage'])
                        && isset($json['ids'])
                    ) {

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

                } else {

                    $message = isset($json['message']) ? $json['message'] : null;
                    $code = isset($json['code']) ? $json['code'] : null;

                    if ($code) {

                        if ($code === '0x0216') {

                            throw new InsufficientFundsException($code, $response);

                        }

                        if ($code === '0x0308') {

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
                                    if ($aMessage instanceof \stdClass) {

                                        unset($aMessage->sendtime);

                                    } else {

                                        // If a JSON-parsed job was passed into this method using associative arrays
                                        unset($aMessage['sendtime']);

                                    }
                                }
                            }
                            unset($aMessage);

                            return $this->deliverMessages($SMSMessages);

                        }

                    }

                    if ($response->getStatusCode() === 401) {

                        throw new UnauthorizedException(
                            $message, $code, $response
                        );

                    }

                    if ($response->getStatusCode() === 422) {

                        throw new MessageException(
                            $message, $code, $response
                        );

                    }

                    throw new BaseException(
                        $message, $code, $response
                    );

                }

            }

            throw new BaseException("Failed to parse response from GatewayAPI.", null, $response);

        } catch (TransferException $exception) {

            throw new BaseException(
                "Failed to connect to GatewayAPI to send SMS: " . $exception->getMessage(), null, null
            );

        }

    }

    /**
     * @return AccountBalance
     * @throws UnauthorizedException
     * @throws BaseException
     */
    public function getCreditStatus(): AccountBalance
    {

        try {

            $response = $this->client->get('/rest/me');

            $json = json_decode($response->getBody(), true);

            if ($json) {

                if ($response->getStatusCode() === 200) {

                    if (isset($json['credit'])
                        && isset($json['currency'])
                        && isset($json['id'])
                    ) {

                        return new AccountBalance($json['credit'], $json['currency'], $json['id']);

                    }

                } else {

                    $message = isset($json['message']) ? $json['message'] : null;
                    $code = isset($json['code']) ? $json['code'] : null;

                    if ($response->getStatusCode() === 401) {

                        throw new UnauthorizedException(
                            $message, $code, $response
                        );

                    }

                    throw new BaseException(
                        $message, $code, $response
                    );

                }

            }

            throw new BaseException(
                'Failed to parse response from GatewayAPI.', null, $response
            );

        } catch (TransferException $exception) {

            throw new BaseException('Connection to GatewayAPI failed: ' . $exception->getMessage(), null, null);

        }

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
     */
    public static function getPricesAsJSON(): array
    {

        try {

            $response = (new Client())->get(
                '/api/prices/list/sms/json',
                [
                    'connect_timeout' => 15,
                    'timeout'         => 30,
                    'http_errors'     => false
                ]
            );

            $json = json_decode($response->getBody(), true);

            if ($json) {

                if ($response->getStatusCode() === 200) {

                    if (isset($json['standard'])
                        && isset($json['premium'])
                        && is_array($json['standard'])
                        && is_array($json['premium'])
                    ) {

                        return $json;

                    }

                } else {

                    $message = isset($json['message']) ? $json['message'] : null;
                    $code = isset($json['code']) ? $json['code'] : null;

                    throw new BaseException(
                        $message, $code, $response
                    );

                }
            }

            throw new BaseException(
                "Failed to parse response from GatewayAPI.", null, $response
            );

        } catch (TransferException $exception) {

            throw new BaseException(
                "Failed to connect to GatewayAPI to fetch prices: " . $exception->getMessage(), null, null
            );

        }

    }


}