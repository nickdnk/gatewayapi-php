<?php


namespace nickdnk\GatewayAPI\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * Class BaseException
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class BaseException extends \Exception
{

    private $gatewayAPIErrorCode, $response;

    public function __construct(?string $message, ?string $gatewayAPIErrorCode, ?ResponseInterface $response)
    {

        // Don't use getCode, as it will always be 1. GatewayAPI returns 0xXXXX codes as strings.
        parent::__construct($message ?? 'No error message defined.', 1);
        $this->gatewayAPIErrorCode = $gatewayAPIErrorCode;
        $this->response = $response;
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

    /**
     * Returns the full HTTP response from GatewayAPI, if any. null if the request failed ano no response was received.
     * You can use this to implement your own error logic, retrieve the HTTP status code, log the full body or whatever
     * you want.
     *
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {

        return $this->response;
    }


}