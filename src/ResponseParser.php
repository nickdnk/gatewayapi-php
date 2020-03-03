<?php


namespace nickdnk\GatewayAPI;

use nickdnk\GatewayAPI\Exceptions\AlreadyCanceledOrSentException;
use nickdnk\GatewayAPI\Exceptions\ConnectionException;
use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\GatewayServerException;
use nickdnk\GatewayAPI\Exceptions\InsufficientFundsException;
use nickdnk\GatewayAPI\Exceptions\MessageException;
use nickdnk\GatewayAPI\Exceptions\PastSendTimeException;
use nickdnk\GatewayAPI\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;

class ResponseParser
{

    /**
     * @param ResponseInterface $response
     *
     * @return array|null|string
     * @throws GatewayRequestException
     * @throws GatewayServerException
     */
    public static function jsonDecodeResponse(ResponseInterface $response)
    {

        if ($response->getStatusCode() === 204) {
            // There is no reason to attempt to decode a 204 as the body must be empty according to HTTP spec.
            return null;
        }

        $json = json_decode($response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {

            $error = 'Failed to parse error response from GatewayAPI: ' . json_last_error_msg();

            if ($response->getStatusCode() >= 500) {

                /**
                 * Because 500-range errors may not return JSON (proxy errors in HTML etc.) we throw this exception
                 * in cases where the response code is >= 500.
                 */
                throw new GatewayServerException($error, null, $response);
            }

            /**
             * Else we throw a regular request exception.
             */
            throw new GatewayRequestException($error, null, $response);

        }

        return $json;

    }

    /**
     * @param ResponseInterface $response
     *
     * @return GatewayRequestException|AlreadyCanceledOrSentException|InsufficientFundsException|MessageException|PastSendTimeException|UnauthorizedException|GatewayServerException|ConnectionException
     */
    public static function handleErrorResponse(ResponseInterface $response)
    {

        if ($response->getStatusCode() === 410) {

            return new AlreadyCanceledOrSentException($response);

        }

        try {

            // This is a little odd, but to avoid code repetition we re-use this method and catch and return exceptions.
            $json = self::jsonDecodeResponse($response);

        } catch (GatewayRequestException $e) {

            return $e;

        }

        $message = isset($json['message']) ? $json['message'] : null;
        $code = isset($json['code']) ? $json['code'] : null;

        if ($code === '0x0216') {
            return new InsufficientFundsException('Your GatewayAPI account has insufficient funds.', $code, $response);
        }

        if ($code === '0x0308') {
            return new PastSendTimeException('Message send time is in the past.', $code, $response);
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

        if ($response->getStatusCode() >= 500) {

            return new GatewayServerException(
                $message, $code, $response
            );

        }

        return new GatewayRequestException(
            $message, $code, $response
        );

    }


}
