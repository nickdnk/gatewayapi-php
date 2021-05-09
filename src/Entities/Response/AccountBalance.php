<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class AccountBalance
 *
 * @package nickdnk\GatewayAPI
 */
class AccountBalance
{

    use Constructable;

    private $credit, $currency, $id;

    public function __construct(float $credit, string $currency, int $id)
    {

        $this->credit = $credit;
        $this->currency = $currency;
        $this->id = $id;
    }

    /**
     *
     * The current balance of your account.
     *
     * @return float
     */
    public function getCredit(): float
    {

        return $this->credit;
    }

    /**
     *
     * The currency your account is settled in.
     *
     * @return string
     */
    public function getCurrency(): string
    {

        return $this->currency;
    }

    /**
     *
     * Returns your account ID at gatewayapi.com
     *
     * @return int
     */
    public function getId(): int
    {

        return $this->id;
    }

    /**
     * @inheritDoc
     * @return AccountBalance
     */
    public static function constructFromArray(array $array): AccountBalance
    {

        if (array_key_exists('credit', $array)
            && array_key_exists('currency', $array)
            && array_key_exists('id', $array)
            && is_float($array['credit'])
            && is_string($array['currency'])
            && is_integer($array['id'])) {

            return new AccountBalance($array['credit'], $array['currency'], $array['id']);

        }

        throw new InvalidArgumentException('Array passed to ' . self::class . ' is missing required parameters.');

    }
}

