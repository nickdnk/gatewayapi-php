<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

class GatewayRequestException extends BaseException
{

    private $gatewayAPIErrorCode, $response;

    /**
     * GatewayRequestException constructor.
     *
     * This exceptions is thrown in any situation where the request completes but fails (> 200 or fails parsing).
     *
     * @param string|null       $message
     * @param string|null       $gatewayAPIErrorCode
     * @param ResponseInterface $response
     */
    public function __construct(?string $message, ?string $gatewayAPIErrorCode, ResponseInterface $response)
    {

        parent::__construct($message);
        $this->gatewayAPIErrorCode = $gatewayAPIErrorCode;
        $this->response = $response;
    }

    /**
     * The response is always available for requests that completed, so we override nullability here as well.
     *
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {

        return $this->response;
    }

    /**
     * To see a complete list of error codes and what they mean, visit:
     *
     * @link https://gatewayapi.com/docs/errors.html
     *
     * The error code is null if GatewayAPI returns an invalid response that we cannot parse using their normal error
     * response structure, or if the connection to their servers was not successful at all
     * (timeout, DNS issue, firewall etc.). You should always check if the error code is null before using it.
     *
     * @return string|null string
     */
    public function getGatewayAPIErrorCode(): ?string
    {

        return $this->gatewayAPIErrorCode;
    }


}
