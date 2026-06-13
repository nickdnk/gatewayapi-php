<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use Psr\Http\Message\ResponseInterface;

trait Constructable
{

    /**
     * Takes an array (such as the output of json_decode($obj, true)) and must return an instance of self.
     * This method should throw an InvalidArgumentException if the array contains invalid data.
     *
     * @throws InvalidArgumentException
     */
    abstract public static function constructFromArray(array $array): static;

    /**
     * Takes a JSON string and returns an instance of the Constructable using the abstract constructFromArray()
     * function which must be implemented by the subclass.
     *
     * @throws InvalidArgumentException If $throwExceptions is true and the JSON is invalid, or if
     *                                  constructFromArray() rejects the decoded data.
     */
    public static function constructFromJSON(string $json, bool $throwExceptions = true): static
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
     * @throws SuccessfulResponseParsingException
     */
    public static function constructFromResponse(ResponseInterface $response): static
    {

        try {

            return static::constructFromJSON((string)$response->getBody());

        } catch (InvalidArgumentException $exception) {

            throw new SuccessfulResponseParsingException(
                'Failed to construct \'' . static::class . '\' from: ' . $response->getBody(), $response
            );

        }

    }

}
