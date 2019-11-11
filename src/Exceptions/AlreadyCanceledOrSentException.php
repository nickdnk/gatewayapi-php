<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Class AlreadyCanceledOrSentException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class AlreadyCanceledOrSentException extends BaseException
{

    /**
     * This error is thrown if your account doesn't have enough credits to
     * send the messages passed to deliverMessages().
     *
     * @param int               $id
     * @param ResponseInterface $response
     */
    public function __construct(int $id, ResponseInterface $response)
    {

        parent::__construct('Message with ID ' . $id . ' has already been canceled or sent.', null, $response);
    }
}