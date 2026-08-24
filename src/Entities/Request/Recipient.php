<?php


namespace nickdnk\GatewayAPI\Entities\Request;

use InvalidArgumentException;
use JsonSerializable;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class Recipient
 *
 * @package nickdnk\GatewayAPI
 */
final readonly class Recipient implements JsonSerializable
{

    use Constructable;

    private int $msisdn;
    private array $tagvalues;
    private ?string $countryCode;

    public static function constructFromArray(array $array): Recipient
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

    /**
     * @param string[] $tagValues
     */
    public function __construct(int $phoneNumber, array $tagValues = [], ?string $countryCode = null)
    {

        $this->msisdn = $phoneNumber;
        $this->tagvalues = $tagValues;
        $this->countryCode = $countryCode;
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

        return $this->tagvalues;
    }


    /**
     * This field is not used by the GatewayAPI API. It's a convenience-method implemented to enable filtering
     * of Recipients based on their country at a later time than construction.
     */
    public function getCountryCode(): string
    {

        if ($this->countryCode === null) {
            throw new InvalidArgumentException('Country code is undefined for Recipient.');
        }

        return $this->countryCode;
    }


}
