<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Exception;

/**
 * Class BaseException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
abstract class BaseException extends Exception
{

    public function __construct(?string $message)
    {

        // Don't use getCode, as it will always be 1. GatewayAPI returns 0xXXXX codes as strings.
        parent::__construct($message ?? 'No error message defined.', 1);

    }

}
