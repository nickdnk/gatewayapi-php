<?php


namespace nickdnk\GatewayAPI;

class DeliveryStatusWebhook extends Webhook
{

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

    private $timestamp, $status, $userReference, $chargeStatus, $countryCode, $countryPrefix, $errorDescription, $errorCode;

    public function __construct(int $messageId, int $phoneNumber, int $timestamp, string $status, ?string $userReference,
        ?string $chargeStatus, ?string $countryCode, ?int $countryPrefix, ?string $errorDescription, ?string $errorCode
    )
    {

        parent::__construct($messageId, $phoneNumber);
        $this->timestamp = $timestamp;
        $this->status = $status;
        $this->userReference = $userReference;
        $this->chargeStatus = $chargeStatus;
        $this->countryCode = $countryCode;
        $this->countryPrefix = $countryPrefix;
        $this->errorDescription = $errorDescription;
        $this->errorCode = $errorCode;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {

        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {

        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getUserReference(): ?string
    {

        return $this->userReference;
    }

    /**
     * @return string|null
     */
    public function getChargeStatus(): ?string
    {

        return $this->chargeStatus;
    }

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string
    {

        return $this->countryCode;
    }

    /**
     * @return int|null
     */
    public function getCountryPrefix(): ?int
    {

        return $this->countryPrefix;
    }

    /**
     * @return string|null
     */
    public function getErrorDescription(): ?string
    {

        return $this->errorDescription;
    }

    /**
     * @return string|null
     */
    public function getErrorCode(): ?string
    {

        return $this->errorCode;
    }


}