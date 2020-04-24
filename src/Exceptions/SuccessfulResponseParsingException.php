<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

class SuccessfulResponseParsingException extends GatewayRequestException
{

    /**
     * This exceptions is thrown if the library fails to parse a successful response. This should not happen. You
     * should never retry a request that causes this exception as it indicates that something is wrong with the library
     * or that gatewayapi.com changed their response structure. This differs from GatewayRequestException in the sense
     * that this happens if the response is valid JSON but is missing required properties.
     *
     * If you have a system in place that automatically retries failed requests you should definitely catch this error
     * and stop retrying, as you could risk going into an infinite loop that keeps sending out messages until you run
     * out of credit or something similarly terrible happens.
     *
     * @param string            $message
     * @param ResponseInterface $response
     */

    public function __construct(string $message, ResponseInterface $response)
    {

        parent::__construct($message, null);
        $this->setResponse($response);

    }
}