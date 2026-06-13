<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Exceptions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class GatewayRequestException extends BaseException
{

    private ?ResponseInterface $response = null;

    /**
     * GatewayRequestException constructor.
     *
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

    /**
     * @param ResponseInterface $response
     */
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
     *
     * @return string|null string
     */
    public function getGatewayAPIErrorCode(): ?string
    {

        return $this->gatewayAPIErrorCode;
    }

    /**
     * Decodes a JSON error body into the appropriate exception. Unlike the entity
     * Constructable trait, this hierarchy's factories are polymorphic (a 401 body yields
     * an UnauthorizedException, etc.), so the return type cannot be `static`.
     *
     * @return GatewayRequestException|InsufficientFundsException
     * @throws InvalidArgumentException If $throwExceptions is true and the body is not valid JSON.
     */
    public static function constructFromJSON(string $json, bool $throwExceptions = true): self
    {

        $array = json_decode($json, true);

        if ($throwExceptions) {

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('Failed to parse string as valid JSON.');
            }

            if (!$array || !is_array($array)) {
                throw new InvalidArgumentException('Invalid JSON passed to ' . static::class);
            }

        }

        return static::constructFromArray($array ?? []);

    }

    /**
     * @param ResponseInterface $response
     *
     * @return GatewayRequestException|GatewayServerException|MessageException|UnauthorizedException|InsufficientFundsException
     */
    public static function constructFromResponse(ResponseInterface $response): self
    {

        $body = (string)$response->getBody();

        if ($response->getStatusCode() === 401) {
            $error = UnauthorizedException::constructFromJSON($body, false);
        } elseif ($response->getStatusCode() === 422) {
            $error = MessageException::constructFromJSON($body, false);
        } elseif ($response->getStatusCode() >= 500) {
            $error = GatewayServerException::constructFromJSON($body, false);
        } else {
            $error = GatewayRequestException::constructFromJSON($body, false);
        }

        $error->setResponse($response);

        return $error;

    }

    /**
     * Parses an error body. Handles both the legacy REST error shape
     * (`{message, code}`, where `code` is a hex string) and the messaging API's
     * validation error shape (`{detail: [{loc, msg, type}, ...]}`).
     *
     * @param array $array
     *
     * @return GatewayRequestException|InsufficientFundsException
     */
    public static function constructFromArray(array $array)
    {

        if (array_key_exists('code', $array) && $array['code'] === '0x0216') {
            return InsufficientFundsException::constructFromArray($array);
        }

        // Messaging API (FastAPI/Pydantic) validation error.
        if (array_key_exists('detail', $array) && is_array($array['detail'])) {

            $messages = [];

            foreach ($array['detail'] as $detail) {
                if (is_array($detail) && isset($detail['msg'])) {
                    $messages[] = is_array($detail['loc'] ?? null)
                        ? implode('.', $detail['loc']) . ': ' . $detail['msg']
                        : $detail['msg'];
                }
            }

            return new static($messages ? implode('; ', $messages) : 'Validation error.', null);

        }

        return new static(
            isset($array['message']) ? $array['message'] : null, isset($array['code']) ? $array['code'] : null
        );
    }
}
