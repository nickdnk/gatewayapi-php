<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

class GatewayRequestException extends BaseException
{

    /**
     * GatewayRequestException constructor.
     *
     * Overrides the parent without a nullable response.
     *
     * @param string|null       $message
     * @param string|null       $gatewayAPIErrorCode
     * @param ResponseInterface $response
     */
    public function __construct(?string $message, ?string $gatewayAPIErrorCode, ResponseInterface $response)
    {

        parent::__construct($message, $gatewayAPIErrorCode, $response);
    }

    /**
     * The response is always available for requests that completed, so we override nullability here as well.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {

        return parent::getResponse();
    }
}