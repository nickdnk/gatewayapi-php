<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Class MessageFormattingException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class MessageException extends BaseException
{

    /**
     * MessageFormattingException constructor.
     *
     * This error is thrown in any situation where there's a formatting issue with your message, such as incorrect
     * use of tags or duplicate recipients. This error is also thrown for filtered or blocked messages. Inspect the
     * error code or response body to find out the exact problem.
     *
     * @param string            $code
     * @param ResponseInterface $response
     */
    public function __construct(string $code, ResponseInterface $response)
    {

        parent::__construct('Request contains one or more invalid messages.', $code, $response);
    }
}