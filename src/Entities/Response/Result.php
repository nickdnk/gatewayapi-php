<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Response;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class Result
 *
 * Parses the response from the messaging API's `/mobile/multi` (and `/mobile/single`)
 * endpoints. The messaging API no longer returns usage data (cost, currency, per-country
 * counts), so those accessors are retained for backwards compatibility but return neutral
 * values. Message IDs are now ULID strings rather than integers.
 *
 * @package nickdnk\GatewayAPI
 */
class Result
{

    use Constructable;

    /**
     * @param float    $totalCost
     * @param int      $smsCount
     * @param string   $currency
     * @param array    $countries
     * @param string[] $messageIds
     */
    public function __construct(
        private readonly float $totalCost,
        private readonly int $smsCount,
        private readonly string $currency,
        private readonly array $countries,
        private readonly array $messageIds
    )
    {
    }

    /**
     * @deprecated The messaging API no longer returns usage cost. Always returns 0.0.
     */
    public function getTotalCost(): float
    {

        return $this->totalCost;
    }

    /**
     * Returns the total number of messages accepted by the messaging API.
     */
    public function getTotalSMSCount(): int
    {

        return $this->smsCount;
    }

    /**
     * @deprecated The messaging API no longer returns a currency. Always returns an empty string.
     */
    public function getCurrency(): string
    {

        return $this->currency;
    }

    /**
     * @deprecated The messaging API no longer returns per-country counts. Always returns an empty array.
     */
    public function getCountries(): array
    {

        return $this->countries;
    }

    /**
     * Returns the IDs of all messages accepted by the messaging API, in the order they
     * were submitted. These are ULID strings (e.g. `01JNN696A9E0WS89FPYGT15NBX`).
     *
     * @return string[]
     */
    public function getMessageIds(): array
    {

        return $this->messageIds;
    }

    /**
     * @throws InvalidArgumentException If the response shape is not recognised.
     */
    public static function constructFromArray(array $array): static
    {

        // `/mobile/multi` returns an envelope of responses; `/mobile/single` returns one.
        if (array_key_exists('responses', $array) && is_array($array['responses'])) {
            $responses = $array['responses'];
        } elseif (array_key_exists('msg_id', $array)) {
            $responses = [$array];
        } else {
            throw new InvalidArgumentException('Array passed to ' . self::class . ' is missing required parameters.');
        }

        $messageIds = [];

        foreach ($responses as $response) {

            if (!is_array($response) || !array_key_exists('msg_id', $response)) {
                throw new InvalidArgumentException('Array passed to ' . self::class . ' contains an invalid response.');
            }

            $messageIds[] = $response['msg_id'];

        }

        return new self(0.0, count($messageIds), '', [], $messageIds);

    }
}
