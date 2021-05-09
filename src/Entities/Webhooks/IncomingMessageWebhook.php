<?php


namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Entities\Constructable;
use nickdnk\GatewayAPI\Exceptions\WebhookException;

/**
 * Class IncomingMessageWebhook
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
class IncomingMessageWebhook extends Webhook
{

    use Constructable;

    private $receiver, $message, $timestamp, $webhookLabel, $senderName, $mcc, $mnc, $validityPeriod, $encoding, $udh, $payload, $countryCode, $countryPrefix;

    protected function __construct(int $messageId, int $phoneNumber, int $receiver, string $message, int $timestamp,
        string $webhookLabel, ?string $senderName, ?int $mcc, ?int $mnc, ?int $validityPeriod, ?string $encoding,
        ?string $udh, ?string $payload, ?string $countryCode, ?int $countryPrefix
    )
    {

        parent::__construct($messageId, $phoneNumber);
        $this->receiver = $receiver;
        $this->message = $message;
        $this->timestamp = $timestamp;
        $this->webhookLabel = $webhookLabel;
        $this->senderName = $senderName;
        $this->mcc = $mcc;
        $this->mnc = $mnc;
        $this->validityPeriod = $validityPeriod;
        $this->encoding = $encoding;
        $this->udh = $udh;
        $this->payload = $payload;
        $this->countryCode = $countryCode;
        $this->countryPrefix = $countryPrefix;
    }

    /**
     * @return int
     */
    public function getReceiver(): int
    {

        return $this->receiver;
    }

    /**
     * @return string
     */
    public function getMessageText(): string
    {

        return $this->message;
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
    public function getWebhookLabel(): string
    {

        return $this->webhookLabel;
    }

    /**
     * @return string|null
     */
    public function getSenderName(): ?string
    {

        return $this->senderName;
    }

    /**
     * @return int|null
     */
    public function getMcc(): ?int
    {

        return $this->mcc;
    }

    /**
     * @return int|null
     */
    public function getMnc(): ?int
    {

        return $this->mnc;
    }

    /**
     * @return int|null
     */
    public function getValidityPeriod(): ?int
    {

        return $this->validityPeriod;
    }

    /**
     * @return string|null
     */
    public function getEncoding(): ?string
    {

        return $this->encoding;
    }

    /**
     * @return string|null
     */
    public function getUdh(): ?string
    {

        return $this->udh;
    }

    /**
     * @return string|null
     */
    public function getPayload(): ?string
    {

        return $this->payload;
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
     * @inheritDoc
     * @return IncomingMessageWebhook
     * @throws WebhookException
     */
    public static function constructFromArray(array $array): IncomingMessageWebhook
    {

        if (array_key_exists('id', $array)
            && array_key_exists('msisdn', $array)
            && array_key_exists('receiver', $array)
            && array_key_exists('message', $array)
            && array_key_exists('senttime', $array)
            && array_key_exists('webhook_label', $array)) {

            return new self(
                $array['id'],
                $array['msisdn'],
                $array['receiver'],
                $array['message'],
                $array['senttime'],
                $array['webhook_label'],
                array_key_exists('sender', $array) ? $array['sender'] : null,
                array_key_exists('mcc', $array) ? $array['mcc'] : null,
                array_key_exists('mnc', $array) ? $array['mnc'] : null,
                array_key_exists('validity_period', $array) ? $array['validity_period'] : null,
                array_key_exists('encoding', $array) ? $array['encoding'] : null,
                array_key_exists('udh', $array) ? $array['udh'] : null,
                array_key_exists('payload', $array) ? $array['payload'] : null,
                array_key_exists('country_code', $array) ? $array['country_code'] : null,
                array_key_exists('country_prefix', $array) ? $array['country_prefix'] : null
            );

        }

        throw new WebhookException(
            self::class . ' missing required keys. Got: ' . implode(',', array_keys($array))
        );

    }
}
