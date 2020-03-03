<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use nickdnk\GatewayAPI\ResponseParser;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AccountBalance
 *
 * @package nickdnk\GatewayAPI
 */
class AccountBalance implements ResponseEntity
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
    public static function constructFromResponse(ResponseInterface $response): ResponseEntity
    {

        $json = ResponseParser::jsonDecodeResponse($response);

        if (is_array($json)
            && array_key_exists('credit', $json)
            && array_key_exists('currency', $json)
            && array_key_exists('id', $json)
            && is_float($json['credit'])
            && is_string($json['currency'])
            && is_integer($json['id'])) {

            return new AccountBalance($json['credit'], $json['currency'], $json['id']);

        }

        throw new SuccessfulResponseParsingException(
            'Failed to parse AccountBalance from: ' . json_encode($json), $response
        );
    }
}

