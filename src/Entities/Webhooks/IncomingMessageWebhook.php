<?php


namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Entities\Constructable;
use nickdnk\GatewayAPI\Exceptions\WebhookException;

/**
 * Class IncomingMessageWebhook
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
final readonly class IncomingMessageWebhook extends Webhook
{

    use Constructable;

    protected function __construct(int $messageId, int $phoneNumber, private int $receiver,
        private string $message, private int $timestamp, private string $webhookLabel, private ?string $senderName,
        private ?int $mcc, private ?int $mnc, private ?int $validityPeriod, private ?string $encoding,
        private ?string $udh, private ?string $payload, private ?string $countryCode, private ?int $countryPrefix
    ) {

        parent::__construct($messageId, $phoneNumber);
    }

    public function getReceiver(): int
    {

        return $this->receiver;
    }

    public function getMessageText(): string
    {

        return $this->message;
    }

    public function getTimestamp(): int
    {

        return $this->timestamp;
    }

    public function getWebhookLabel(): string
    {

        return $this->webhookLabel;
    }

    public function getSenderName(): ?string
    {

        return $this->senderName;
    }

    public function getMcc(): ?int
    {

        return $this->mcc;
    }

    public function getMnc(): ?int
    {

        return $this->mnc;
    }

    public function getValidityPeriod(): ?int
    {

        return $this->validityPeriod;
    }

    public function getEncoding(): ?string
    {

        return $this->encoding;
    }

    public function getUdh(): ?string
    {

        return $this->udh;
    }

    public function getPayload(): ?string
    {

        return $this->payload;
    }

    public function getCountryCode(): ?string
    {

        return $this->countryCode;
    }

    public function getCountryPrefix(): ?int
    {

        return $this->countryPrefix;
    }

    /**
     * @inheritDoc
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
