<?php


namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Entities\Constructable;
use nickdnk\GatewayAPI\Exceptions\WebhookException;

/**
 * Class DeliveryStatusWebhook
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
final readonly class DeliveryStatusWebhook extends Webhook
{

    use Constructable;

    /**
     * The message status enumerations.
     * Use these constants to avoid typos in your project.
     *
     * @link https://gatewayapi.com/docs/rest.html#delivery-status-notification
     */
    const STATUS_UNKNOWN       = 'UNKNOWN';
    const STATUS_SCHEDULED     = 'SCHEDULED';
    const STATUS_BUFFERED      = 'BUFFERED';
    const STATUS_EN_ROUTE      = 'ENROUTE';
    const STATUS_DELIVERED     = 'DELIVERED';
    const STATUS_EXPIRED       = 'EXPIRED';
    const STATUS_DELETED       = 'DELETED';
    const STATUS_UNDELIVERABLE = 'UNDELIVERABLE';
    const STATUS_ACCEPTED      = 'ACCEPTED';
    const STATUS_REJECTED      = 'REJECTED';
    const STATUS_SKIPPED       = 'SKIPPED';

    const CHARGE_STATUS_NO_CHARGE   = 'NOCHARGE';
    const CHARGE_STATUS_AUTHORIZED  = 'AUTHORIZED';
    const CHARGE_STATUS_CANCELLED   = 'CANCELLED';
    const CHARGE_STATUS_CAPTURED    = 'CAPTURED';
    const CHARGE_STATUS_FAILED      = 'FAILED';
    const CHARGE_STATUS_REFUNDED    = 'REFUNDED';
    const CHARGE_STATUS_REFUND_FAIL = 'REFUND_FAIL';

    protected function __construct(int $messageId, int $phoneNumber, private int $timestamp,
        private string $status, private ?string $userReference, private ?string $chargeStatus,
        private ?string $countryCode, private ?int $countryPrefix, private ?string $errorDescription,
        private ?string $errorCode
    ) {

        parent::__construct($messageId, $phoneNumber);
    }

    public function getTimestamp(): int
    {

        return $this->timestamp;
    }

    public function getStatus(): string
    {

        return $this->status;
    }

    public function getUserReference(): ?string
    {

        return $this->userReference;
    }

    public function getChargeStatus(): ?string
    {

        return $this->chargeStatus;
    }

    public function getCountryCode(): ?string
    {

        return $this->countryCode;
    }

    public function getCountryPrefix(): ?int
    {

        return $this->countryPrefix;
    }

    public function getErrorDescription(): ?string
    {

        return $this->errorDescription;
    }

    public function getErrorCode(): ?string
    {

        return $this->errorCode;
    }


    /**
     * @inheritDoc
     * @throws WebhookException
     */
    public static function constructFromArray(array $array): DeliveryStatusWebhook
    {

        if (array_key_exists('id', $array)
            && array_key_exists('msisdn', $array)
            && array_key_exists('time', $array)
            && array_key_exists('status', $array)) {

            return new self(
                $array['id'],
                $array['msisdn'],
                $array['time'],
                $array['status'],
                array_key_exists('userref', $array) ? $array['userref'] : null,
                array_key_exists('charge_status', $array) ? $array['charge_status'] : null,
                array_key_exists('country_code', $array) ? $array['country_code'] : null,
                array_key_exists('country_prefix', $array) ? $array['country_prefix'] : null,
                array_key_exists('error', $array) ? $array['error'] : null,
                array_key_exists('code', $array) ? $array['code'] : null
            );

        }

        throw new WebhookException(
            self::class . ' missing required keys. Got: ' . implode(',', array_keys($array))
        );

    }
}
