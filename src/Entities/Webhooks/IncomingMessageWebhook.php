<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Entities\Constructable;
use nickdnk\GatewayAPI\Exceptions\WebhookException;

/**
 * Class IncomingMessageWebhook
 *
 * Represents an inbound `user-message.*` webhook event: incoming SMS/RCS text, RCS
 * location or RCS file.
 *
 * The inner `event` carries the originating MSISDN in `sender` (not `recipient`), a
 * `sent_at` timestamp, and a `content` field whose shape depends on the event type:
 * - `user-message.text.sms`: object `{message, encoding, header}`
 * - `user-message.text.rcs`: a plain string
 * - `user-message.location.rcs`: object `{latitude, longitude}`
 * - `user-message.file.rcs`: object `{category, thumbnail, payload}`
 *
 * Use {@see IncomingMessageWebhook::getContent()} for the raw, type-specific payload.
 * {@see IncomingMessageWebhook::getMessageText()} is a convenience for the two text types.
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
class IncomingMessageWebhook extends Webhook
{

    use Constructable;

    /**
     * @param string|array $content The raw `content` payload (string for RCS text, array otherwise).
     */
    protected function __construct(string $eventId, string $eventTimestamp, EventType $eventType, string $messageId,
        int $sender,
        private readonly string $sentAt,
        private readonly string|array $content
    )
    {

        parent::__construct($eventId, $eventTimestamp, $eventType, $messageId, $sender);
    }

    /**
     * The MSISDN that sent the message. Alias of {@see Webhook::getPhoneNumber()}.
     */
    public function getSender(): int
    {

        return $this->getPhoneNumber();
    }

    /**
     * The ISO-8601 timestamp of when the message was sent (`sent_at`).
     */
    public function getSentAt(): string
    {

        return $this->sentAt;
    }

    /**
     * The raw, type-specific `content`. A string for `user-message.text.rcs`; otherwise
     * an associative array (text.sms: `message/encoding/header`; location.rcs:
     * `latitude/longitude`; file.rcs: `category/thumbnail/payload`).
     *
     * @return string|array
     */
    public function getContent(): string|array
    {

        return $this->content;
    }

    /**
     * The text body for the two text event types, or null for location/file events.
     */
    public function getMessageText(): ?string
    {

        if (is_string($this->content)) {
            return $this->content;
        }

        if (isset($this->content['message']) && is_string($this->content['message'])) {
            return $this->content['message'];
        }

        return null;
    }

    /**
     * The encoding of an incoming `user-message.text.sms`, if present.
     */
    public function getEncoding(): ?string
    {

        if (is_array($this->content)
            && isset($this->content['encoding'])
            && is_string($this->content['encoding'])) {
            return $this->content['encoding'];
        }

        return null;
    }

    /**
     * @inheritDoc
     * @return IncomingMessageWebhook
     * @throws WebhookException
     */
    public static function constructFromArray(array $array): static
    {

        if (!array_key_exists('event_id', $array)
            || !array_key_exists('timestamp', $array)
            || !array_key_exists('event_type', $array)
            || !array_key_exists('event', $array)
            || !is_array($array['event'])) {

            throw new WebhookException(
                self::class . ' missing required envelope keys. Got: ' . implode(',', array_keys($array))
            );

        }

        $event = $array['event'];

        if (!array_key_exists('msg_id', $event)
            || !array_key_exists('sender', $event)
            || !array_key_exists('sent_at', $event)
            || !array_key_exists('content', $event)
            || !(is_string($event['content']) || is_array($event['content']))) {

            throw new WebhookException(
                self::class . ' missing or invalid required event keys. Got: ' . implode(',', array_keys($event))
            );

        }

        $eventType = EventType::fromString($array['event_type']);

        return new self(
            $array['event_id'],
            $array['timestamp'],
            $eventType,
            $event['msg_id'],
            $event['sender'],
            $event['sent_at'],
            $event['content']
        );

    }
}
