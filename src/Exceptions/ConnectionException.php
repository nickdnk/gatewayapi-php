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
     * This error is thrown if your account doesn't have enough credits to
     * send the messages passed to deliverMessages().
     *
     * @param string $error
     */
    public function __construct(string $error)
    {

        parent::__construct($error, null, null);
    }
}