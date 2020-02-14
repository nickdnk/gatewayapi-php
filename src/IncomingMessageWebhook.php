<?php


namespace nickdnk\GatewayAPI;

use nickdnk\GatewayAPI\Exceptions\WebhookException;
use Psr\Http\Message\RequestInterface;

class IncomingMessageWebhook
{

    private $messageId, $phoneNumber, $receiver, $message, $timestamp, $webhookLabel, $senderName, $mcc, $mnc, $validityPeriod, $encoding, $udh, $payload, $countryCode, $countryPrefix;


    public function __construct(int $messageId, int $phoneNumber, int $receiver, string $message, int $timestamp,
        string $webhookLabel, ?string $senderName, ?int $mcc, ?int $mnc, ?int $validityPeriod, ?string $encoding,
        ?string $udh, ?string $payload, ?string $countryCode, ?int $countryPrefix
    )
    {

        $this->messageId = $messageId;
        $this->phoneNumber = $phoneNumber;
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
     * @param RequestInterface $request
     * @param string           $secret
     *
     * @return static
     * @throws WebhookException
     */
    public static function constructFromRequest(RequestInterface $request, string $secret): self
    {

        $data = GatewayAPIHandler::parseAndValidateJWTFromRequest($request, $secret);

        if (!array_key_exists('id', $data)
            || !array_key_exists('msisdn', $data)
            || !array_key_exists('receiver', $data)
            || !array_key_exists('message', $data)
            || !array_key_exists('senttime', $data)
            || !array_key_exists('webhook_label', $data)) {

            throw new WebhookException(
                'Webhook missing required keys. Got: ' . implode(',', array_keys($data))
            );

        }

        return new self(
            $data['id'],
            $data['msisdn'],
            $data['receiver'],
            $data['message'],
            $data['senttime'],
            $data['webhook_label'],
            array_key_exists('sender', $data) ? $data['sender'] : null,
            array_key_exists('mcc', $data) ? $data['mcc'] : null,
            array_key_exists('mnc', $data) ? $data['mnc'] : null,
            array_key_exists('validity_period', $data) ? $data['validity_period'] : null,
            array_key_exists('encoding', $data) ? $data['encoding'] : null,
            array_key_exists('udh', $data) ? $data['udh'] : null,
            array_key_exists('payload', $data) ? $data['payload'] : null,
            array_key_exists('country_code', $data) ? $data['country_code'] : null,
            array_key_exists('country_prefix', $data) ? $data['country_prefix'] : null
        );

    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {

        return $this->messageId;
    }

    /**
     * @return int
     */
    public function getPhoneNumber(): int
    {

        return $this->phoneNumber;
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