<?php


namespace nickdnk\GatewayAPI\Exceptions;

/**
 * Class UnauthorizedException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class UnauthorizedException extends GatewayRequestException
{

    /**
     * This exception is thrown in any case you receive a 401-response with the regular error structure.
     * The error code or response body will tell you more about the particular error, but usually this is because
     * of a bad key/secret combination.
     */
}