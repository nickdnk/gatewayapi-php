<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Response;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Constructable;

class Prices
{

    use Constructable;

    public function __construct(
        private readonly array $standard,
        private readonly array $premium
    )
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

    /**
     * @throws InvalidArgumentException If required keys are missing.
     */
    public static function constructFromArray(array $array): static
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
