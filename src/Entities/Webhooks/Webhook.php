<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Exceptions\WebhookException;
use Psr\Http\Message\RequestInterface;

/**
 * Class Webhook
 *
 * Parses and verifies webhook callbacks from the messaging API.
 *
 * Verification: the messaging API signs the request by computing an HMAC-SHA256 over
 * the raw request body using the webhook secret configured in the dashboard, hex-encodes
 * it, and sends it in the `Signature` header as `v1=<hex>`.
 * @link https://gatewayapi.com/docs/message/overview/#webhook-callbacks
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
abstract class Webhook
{

    protected function __construct(
        private readonly string $eventId,
        private readonly string $timestamp,
        private readonly EventType $eventType,
        private readonly string $messageId,
        private readonly int $phoneNumber
    )
    {
    }

    /**
     * The unique ID of the webhook event (envelope `event_id`, a UUID).
     */
    public function getEventId(): string
    {

        return $this->eventId;
    }

    /**
     * The ISO-8601 timestamp of the webhook envelope.
     */
    public function getEventTimestamp(): string
    {

        return $this->timestamp;
    }

    public function getEventType(): EventType
    {

        return $this->eventType;
    }

    /**
     * The message ULID. Note: this is now a string, not an integer.
     */
    public function getMessageId(): string
    {

        return $this->messageId;
    }

    /**
     * The MSISDN involved in the event: the message recipient for delivery-status events,
     * or the sender for inbound user-message events.
     */
    public function getPhoneNumber(): int
    {

        return $this->phoneNumber;
    }

    /**
     * @param array $data The decoded webhook envelope.
     *
     * @return DeliveryStatusWebhook|IncomingMessageWebhook
     * @throws WebhookException
     */
    private static function constructWebhook(array $data): Webhook
    {

        if (!array_key_exists('event_type', $data)
            || !array_key_exists('event', $data)
            || !is_string($data['event_type'])
            || !is_array($data['event'])) {

            throw new WebhookException(
                'Webhook missing required envelope keys. Got: ' . implode(',', array_keys($data))
            );

        }

        // Route by family prefix rather than the exact enum, so newer event sub-types
        // within a known family still parse (their EventType resolves to UNKNOWN).
        if (str_starts_with($data['event_type'], 'message.status.')) {
            return DeliveryStatusWebhook::constructFromArray($data);
        }

        if (str_starts_with($data['event_type'], 'user-message.')) {
            return IncomingMessageWebhook::constructFromArray($data);
        }

        throw new WebhookException('Unsupported webhook event_type: ' . $data['event_type']);

    }

    /**
     * Verifies the `Signature` header against the raw request body and returns the parsed
     * payload. The signature is `v1=<hex-encoded HMAC-SHA256 of the raw body>`.
     *
     * @throws WebhookException
     */
    private static function verifyAndDecode(string $rawBody, string $signature, string $secret): array
    {

        if (!$signature) {
            throw new WebhookException('Missing webhook Signature header.');
        }

        $expected = 'v1=' . hash_hmac('sha256', $rawBody, $secret);

        if (!hash_equals($expected, $signature)) {
            throw new WebhookException('Webhook failed signature validation.');
        }

        $payload = json_decode($rawBody, true);

        if (!is_array($payload)) {
            throw new WebhookException('Failed to parse webhook body as JSON.');
        }

        return $payload;

    }

    /**
     * Constructs a webhook from a PSR-7 request object. This reads the raw body and the
     * `Signature` header, verifies the HMAC signature and returns one of the possible
     * webhook types.
     *
     * IMPORTANT: the signature is computed over the RAW request body. Make sure the body
     * stream has not been consumed or mutated by middleware before calling this. If your
     * framework has already read the body, use {@see Webhook::constructFromBody()} with
     * the original raw string instead.
     *
     * @return DeliveryStatusWebhook|IncomingMessageWebhook
     * @throws WebhookException
     */
    final public static function constructFromRequest(RequestInterface $request, string $secret): Webhook
    {

        $body = $request->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        return self::constructFromBody(
            (string)$body,
            $request->getHeaderLine('Signature'),
            $secret
        );

    }

    /**
     * Constructs a webhook from a raw request body and the `Signature` header value. Use
     * this when you have already extracted the raw body from the request yourself.
     *
     * @return DeliveryStatusWebhook|IncomingMessageWebhook
     * @throws WebhookException
     */
    final public static function constructFromBody(string $rawBody, string $signature, string $secret): Webhook
    {

        return self::constructWebhook(self::verifyAndDecode($rawBody, $signature, $secret));

    }


}
