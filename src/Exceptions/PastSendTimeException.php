<?php


namespace nickdnk\GatewayAPI\Exceptions;

/**
 * Class PastSendTimeException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class PastSendTimeException extends GatewayRequestException
{

    /**
     * This exception is thrown when the sendtime parameter is in the past.
     */
}