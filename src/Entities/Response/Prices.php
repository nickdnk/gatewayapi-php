<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Constructable;

class Prices
{

    use Constructable;

    private $standard, $premium;

    public function __construct(array $standard, array $premium)
    {

        $this->standard = $standard;
        $this->premium = $premium;
    }

    public function getStandard(): array
    {

        return $this->standard;
    }

    public function getPremium(): array
    {

        return $this->premium;
    }

    public static function constructFromArray(array $array): Prices
    {

        if (isset($array['standard'])
            && isset($array['premium'])
            && is_array($array['standard'])
            && is_array($array['premium'])) {

            return new self($array['standard'], $array['premium']);

        }

        throw new InvalidArgumentException('Array passed to ' . self::class . ' is missing required parameters.');

    }
}
