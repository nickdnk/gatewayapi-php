<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Class UnauthorizedException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class UnauthorizedException extends BaseException
{

    /**
     * UnauthorizedException constructor.
     *
     * This error is thrown in any case you receive a 401-response with the regular error structure.
     * The error code or response body will tell you more about the particular error, but usually this is because
     * of a bad key/secret combination.
     *
     * @param string|null       $message
     * @param string|null       $code
     * @param ResponseInterface $response
     */
    public function __construct(?string $message, ?string $code, ResponseInterface $response)
    {

        parent::__construct($message, $code, $response);
    }
}