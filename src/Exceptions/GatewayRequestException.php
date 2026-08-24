<?php


namespace nickdnk\GatewayAPI\Exceptions;

use nickdnk\GatewayAPI\Entities\Constructable;
use Psr\Http\Message\ResponseInterface;

class GatewayRequestException extends BaseException
{

    use Constructable;

    private ?ResponseInterface $response = null;

    /**
     * This exceptions is thrown in any situation where the request completes but was not successful or fails parsing.
     */
    public function __construct(?string $message, private readonly ?string $gatewayAPIErrorCode)
    {

        parent::__construct($message);
    }

    /**
     * The response is always available for requests that completed, so we override nullability here as well.
     */
    public function getResponse(): ResponseInterface
    {

        return $this->response;
    }

    public function setResponse(ResponseInterface $response): void
    {

        $this->response = $response;
    }

    /**
     * Returns the error code as defined by the gatewayapi.com API.
     * See the link for full documentation.
     *
     * @link https://gatewayapi.com/docs/errors.html
     *
     * The error code is null if GatewayAPI returns an invalid response that we cannot parse using their normal error
     * response structure. You should always check if the error code is null before using it.
     */
    public function getGatewayAPIErrorCode(): ?string
    {

        return $this->gatewayAPIErrorCode;
    }

    /**
     * @return AlreadyCanceledOrSentException|GatewayRequestException|GatewayServerException|MessageException|UnauthorizedException|InsufficientFundsException
     */
    public static function constructFromResponse(ResponseInterface $response)
    {

        if ($response->getStatusCode() === 401) {
            $error = UnauthorizedException::constructFromJSON($response->getBody(), false);
        } elseif ($response->getStatusCode() === 410) {
            $error = new AlreadyCanceledOrSentException();
        } elseif ($response->getStatusCode() === 422) {
            $error = MessageException::constructFromJSON($response->getBody(), false);
        } elseif ($response->getStatusCode() >= 500) {
            $error = GatewayServerException::constructFromJSON($response->getBody(), false);
        } else {
            $error = GatewayRequestException::constructFromJSON($response->getBody(), false);
        }

        $error->setResponse($response);

        return $error;

    }

    /**
     * @return GatewayRequestException|InsufficientFundsException
     */
    public static function constructFromArray(array $array)
    {

        if (array_key_exists('code', $array) && $array['code'] === '0x0216') {
            return InsufficientFundsException::constructFromArray($array);
        }

        return new static(
            isset($array['message']) ? $array['message'] : null, isset($array['code']) ? $array['code'] : null
        );
    }
}
