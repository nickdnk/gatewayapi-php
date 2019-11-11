<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Class PastSendTimeException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class PastSendTimeException extends BaseException
{

    /**
     * This error is thrown when the sendtime parameter is in the past.
     *
     * @param string            $code
     * @param ResponseInterface $response
     */
    public function __construct(string $code, ResponseInterface $response)
    {

        parent::__construct('Message send time is in the past.', $code, $response);
    }
}