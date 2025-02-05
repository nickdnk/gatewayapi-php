<?php


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
     * @param array $array
     *
     * @return static
     * @throws InvalidArgumentException
     */
    abstract public static function constructFromArray(array $array);

    /**
     * Takes a JSON string and returns an instance of the Constructable using the abstract constructFromArray()
     * function which must be implemented by the subclass.
     *
     * @param string $json
     * @param bool   $throwExceptions
     *
     * @return static
     */
    public static function constructFromJSON(string $json, bool $throwExceptions = true)
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
     * @return static
     * @throws SuccessfulResponseParsingException
     */
    public static function constructFromResponse(ResponseInterface $response)
    {

        try {

            return static::constructFromJSON($response->getBody());

        } catch (InvalidArgumentException $exception) {

            throw new SuccessfulResponseParsingException(
                'Failed to construct \'' . static::class . '\' from: ' . $response->getBody(), $response
            );

        }

    }

}
