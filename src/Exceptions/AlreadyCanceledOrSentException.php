<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Class AlreadyCanceledOrSentException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class AlreadyCanceledOrSentException extends GatewayRequestException
{

    /**
     * This exception is thrown if the message IDs provided to cancelScheduledMessages refer to a message that
     * does not exist, is not scheduled (was sent) or has already been canceled.
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {

        parent::__construct('Message has already been canceled or sent.', null, $response);
    }

}
