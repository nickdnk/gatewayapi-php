<?php


namespace nickdnk\GatewayAPI\Exceptions;

/**
 * Class InsufficientFundsException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class InsufficientFundsException extends GatewayRequestException
{

    /**
     * This exception is thrown if your account doesn't have enough credits to
     * send the messages passed to `deliverMessages()`. We have to override the
     * constructor because this is the only exception that's identified by its error code.
     * Without overriding this we end up in an infinite loop.
     */

    public static function constructFromArray(array $array): InsufficientFundsException
    {

        return new self($array['message'], $array['code']);
    }
}
