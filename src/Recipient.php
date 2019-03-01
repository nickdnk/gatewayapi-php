<?php


namespace nickdnk\GatewayAPI;

/**
 * Class Recipient
 *
 * @property int      $msisdn
 * @property string[] $tagvalues
 * @package nickdnk\GatewayAPI
 */
class Recipient implements \JsonSerializable
{

    private $msisdn, $tagvalues, $countryCode;

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
     * @return int
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
     * @return string
     */
    public function getCountryCode(): string
    {

        if ($this->countryCode === null) {
            throw new \InvalidArgumentException('Trying to get country code on Recipient where it has not been set.');
        }

        return $this->countryCode;
    }


}