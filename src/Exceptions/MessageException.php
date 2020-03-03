<?php


namespace nickdnk\GatewayAPI\Exceptions;

/**
 * Class MessageFormattingException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class MessageException extends GatewayRequestException
{

    /**
     * This exception is thrown in any situation where there's a formatting issue with your message, such as incorrect
     * use of tags or duplicate recipients. This error is also thrown for filtered or blocked messages. Inspect the
     * error code or response body to find out the exact problem.
     */
}