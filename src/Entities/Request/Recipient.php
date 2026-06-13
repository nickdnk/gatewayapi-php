<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Request;

use InvalidArgumentException;
use JsonSerializable;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class Recipient
 *
 * @package nickdnk\GatewayAPI
 */
class Recipient implements JsonSerializable
{

    use Constructable;

    /**
     * Recipient constructor.
     *
     * @param int           $msisdn
     * @param string[]|null $tagvalues
     * @param string|null   $countryCode
     */
    public function __construct(
        private readonly int $msisdn,
        private readonly ?array $tagvalues = [],
        private readonly ?string $countryCode = null
    )
    {
    }

    /**
     * @throws InvalidArgumentException If required keys are missing or of the wrong type.
     */
    public static function constructFromArray(array $array): static
    {

        if (array_key_exists('msisdn', $array)
            && array_key_exists('tagvalues', $array)
            && is_int($array['msisdn'])
            && is_array($array['tagvalues'])) {

            return new self(
                $array['msisdn'], $array['tagvalues']
            );

        }

        throw new InvalidArgumentException('Array passed to ' . self::class . ' is missing required parameters.');

    }

    public function jsonSerialize(): array
    {

        return [
            'msisdn'    => $this->msisdn,
            'tagvalues' => $this->tagvalues
        ];
    }

    /**
     * Returns the phone number of the recipient as an integer, also known as the MSISDN.
     *
     * @link    https://en.wikipedia.org/wiki/MSISDN
     * @example 4561273444
     */
    public function getMsisdn(): int
    {

        return $this->msisdn;
    }

    /**
     * @return string[]
     */
    public function getTagValues(): array
    {

        return $this->tagvalues ?? [];
    }


    /**
     * This field is not used by the GatewayAPI API. It's a convenience-method implemented to enable filtering
     * of Recipients based on their country at a later time than construction.
     *
     * @throws InvalidArgumentException If no country code was set on this Recipient.
     */
    public function getCountryCode(): string
    {

        if ($this->countryCode === null) {
            throw new InvalidArgumentException('Country code is undefined for Recipient.');
        }

        return $this->countryCode;
    }


}
