<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Constructable;

final readonly class Prices
{

    use Constructable;

    public function __construct(private array $standard, private array $premium)
    {
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
