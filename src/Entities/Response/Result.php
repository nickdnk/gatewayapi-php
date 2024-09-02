<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use InvalidArgumentException;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class Result
 *
 * @package nickdnk\GatewayAPI
 */
class Result
{

    use Constructable;

    private $totalCost, $smsCount, $currency, $countries, $messageIds;

    /**
     * Result constructor.
     *
     * @param float  $totalCost
     * @param int    $smsCount
     * @param string $currency
     * @param array  $countries
     * @param array  $messageIds
     */
    public function __construct(float $totalCost, int $smsCount, string $currency, array $countries, array $messageIds)
    {

        $this->totalCost = $totalCost;
        $this->smsCount = $smsCount;
        $this->currency = $currency;
        $this->countries = $countries;
        $this->messageIds = $messageIds;
    }

    /**
     *
     * Returns the total cost for the request as a decimal number.
     * Rounded to a maximum of 5 decimal points using `PHP_ROUND_HALF_UP`.
     * In practice, GatewayAPI only uses 4 decimal points, but their transaction
     * log shows 5 digits.
     *
     * @return float
     */
    public function getTotalCost(): float
    {

        return $this->totalCost;
    }

    /**
     *
     * Returns the total number of SMS messages sent to all countries.
     *
     * @return int
     */
    public function getTotalSMSCount(): int
    {

        return $this->smsCount;
    }

    /**
     *
     * Returns the 3-digit currency of the totalCost value, such as `eur` for Euro.
     *
     * @return string
     */
    public function getCurrency(): string
    {

        return $this->currency;
    }

    /**
     *
     * Returns an array of all the countries as key and the number of messages sent to each country as value.
     * For instance, to get the number of messages sent to UK (if any), you could do `$result->getCountries()['UK']`.
     * WARNING: An array key only exists if at least one message was sent to the corresponding country.
     *
     * @return array
     */
    public function getCountries(): array
    {

        return $this->countries;
    }

    /**
     *
     * Returns an array of the IDs of all messages delivered to GatewayAPI in the same order they were added
     * to the request. These IDs can be passed directly into the `cancelScheduledMessages()`-method to cancel messages.
     *
     * @return int[]
     */
    public function getMessageIds(): array
    {

        return $this->messageIds;
    }

    public static function constructFromArray(array $array): Result
    {

        if (array_key_exists('usage', $array)
            && is_array($array['usage'])
            && array_key_exists('ids', $array)
            && is_array($array['ids'])
            && array_key_exists('total_cost', $array['usage'])
            && is_float($array['usage']['total_cost'])
            && array_key_exists('currency', $array['usage'])
            && is_string($array['usage']['currency'])
            && array_key_exists('countries', $array['usage'])
            && is_array($array['usage']['countries'])) {

            $smsCount = 0;

            foreach ($array['usage']['countries'] as $count) {

                $smsCount += $count;

            }

            return new self(
                round($array['usage']['total_cost'], 5),
                $smsCount,
                $array['usage']['currency'],
                $array['usage']['countries'],
                $array['ids']
            );

        }

        throw new InvalidArgumentException('Array passed to ' . self::class . ' is missing required parameters.');

    }
}
