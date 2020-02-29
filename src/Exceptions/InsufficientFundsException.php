<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Class InsufficientFundsException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class InsufficientFundsException extends BaseException
{

    /**
     * This exception is thrown if your account doesn't have enough credits to
     * send the messages passed to deliverMessages().
     *
     * @param string            $code
     * @param ResponseInterface $response
     */
    public function __construct(string $code, ResponseInterface $response)
    {

        parent::__construct('Your GatewayAPI account has insufficient funds.', $code, $response);
    }

}