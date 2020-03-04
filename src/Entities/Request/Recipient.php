<?php


namespace nickdnk\GatewayAPI\Entities\Request;

use InvalidArgumentException;

/**
 * Class Recipient
 *
 * @property int      $msisdn
 * @property string[] $tagvalues
 * @package nickdnk\GatewayAPI
 */
class Recipient implements Constructable
{

    private $msisdn, $tagvalues, $countryCode;


    /**
     * @param string $json
     *
     * @return Recipient
     */
    public static function constructFromJSON(string $json): Constructable
    {

        $array = json_decode($json, true);

        if (!$array) {
            throw new InvalidArgumentException('Invalid JSON passed to Recipient.');
        }

        if (is_array($array)
            && array_key_exists('msisdn', $array)
            && array_key_exists('tagvalues', $array)) {

            return new self(
                $array['msisdn'], $array['tagvalues']
            );

        } else {

            throw new InvalidArgumentException('JSON passed to Recipient is missing required parameters.');

        }

    }

    /**
     * Recipient constructor.
     *
     * @param int           $phoneNumber
     * @param string[]|null $tagValues
     * @param string|null   $countryCode
     */
    public function __construct(int $phoneNumber, ?array $tagValues = [], ?string $countryCode = null)
    {

        $this->msisdn = $phoneNumber;
        $this->tagvalues = $tagValues;
        $this->countryCode = $countryCode;
    }

    public function jsonSerialize()
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
     * @return int
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
     *
     * @return string
     */
    public function getCountryCode(): string
    {

        if ($this->countryCode === null) {
            throw new InvalidArgumentException('Country code is undefined for Recipient.');
        }

        return $this->countryCode;
    }


}
