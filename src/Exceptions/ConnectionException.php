<?php


namespace nickdnk\GatewayAPI\Exceptions;

/**
 * Class ConnectionException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class ConnectionException extends BaseException
{

    /**
     * This exception is thrown if the connection to gatewayapi.com fails.
     *
     * @param string $error
     */
    public function __construct(string $error)
    {

        parent::__construct($error, null, null);
    }
}