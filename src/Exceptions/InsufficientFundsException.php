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
     * send the messages passed to deliverMessages().
     */
}
