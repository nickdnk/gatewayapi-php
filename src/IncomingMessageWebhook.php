<?php


namespace nickdnk\GatewayAPI;

class IncomingMessageWebhook extends Webhook
{

    private $receiver, $message, $timestamp, $webhookLabel, $senderName, $mcc, $mnc, $validityPeriod, $encoding, $udh, $payload, $countryCode, $countryPrefix;

    public function __construct(int $messageId, int $phoneNumber, int $receiver, string $message, int $timestamp,
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


}