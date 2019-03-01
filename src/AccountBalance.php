<?php


namespace nickdnk\GatewayAPI;

/**
 * Class AccountBalance
 *
 * @package nickdnk\GatewayAPI
 */
class AccountBalance
{


    private $credit, $currency, $id;

    /**
     * CurrencyStatus constructor.
     *
     * @param $credit
     * @param $currency
     * @param $id
     */
    public function __construct(float $credit, string $currency, int $id)
    {

        $this->credit = $credit;
        $this->currency = $currency;
        $this->id = $id;
    }

    public function jsonSerialize()
    {

        return [
            "credit"   => $this->credit,
            "currency" => $this->currency,
            "id"       => $this->id,
        ];
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


}

