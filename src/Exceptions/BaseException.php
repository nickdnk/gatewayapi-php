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

    /**
     * BaseException constructor. All exceptions in this project inherit from this one.
     *
     * @param string|null $message
     */
    public function __construct(?string $message)
    {

        // Don't use getCode, as it will always be 1. GatewayAPI returns 0xXXXX codes as strings.
        parent::__construct($message ?? 'No error message defined.', 1);

    }

}
