<?php


namespace nickdnk\GatewayAPI\Exceptions;

/**
 * Class AlreadyCanceledOrSentException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class AlreadyCanceledOrSentException extends GatewayRequestException
{

    /**
     * This exception is thrown if the message IDs provided to `cancelScheduledMessages()` refer to a message that
     * does not exist, is not scheduled (was sent) or has already been canceled.
     */
    public function __construct()
    {

        parent::__construct('Message has already been canceled or sent.', null);

    }

}
