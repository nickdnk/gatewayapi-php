<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class AccountBalance
 *
 * @package nickdnk\GatewayAPI
 */
final readonly class AccountBalance
{

    use Constructable;

    public function __construct(private float $credit, private string $currency, private int $id)
    {
    }

    /**
     *
     * The current balance of your account.
     */
    public function getCredit(): float
    {

        return $this->credit;
    }

    /**
     *
     * The currency your account is settled in.
     */
    public function getCurrency(): string
    {

        return $this->currency;
    }

    /**
     *
     * Returns your account ID at gatewayapi.com
     */
    public function getId(): int
    {

        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public static function constructFromArray(array $array): AccountBalance
    {

        // Apparently credit is now a string, but used to be a float.
        if (array_key_exists('credit', $array)
            && array_key_exists('currency', $array)
            && array_key_exists('id', $array)
            && (is_float($array['credit']) || is_string($array['credit']))
            && is_string($array['currency'])
            && is_integer($array['id'])) {

            return new AccountBalance((float)$array['credit'], $array['currency'], $array['id']);

        }

        throw new InvalidArgumentException('Array passed to ' . self::class . ' is missing required parameters.');

    }
}
