<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Webhooks;

use nickdnk\GatewayAPI\Entities\Constructable;
use nickdnk\GatewayAPI\Exceptions\WebhookException;

/**
 * Class DeliveryStatusWebhook
 *
 * Represents a `message.status.sms` or `message.status.rcs` webhook event.
 *
 * @package nickdnk\GatewayAPI\Entities\Webhooks
 */
class DeliveryStatusWebhook extends Webhook
{

    use Constructable;

    protected function __construct(string $eventId, string $eventTimestamp, EventType $eventType, string $messageId,
        int $phoneNumber,
        private readonly string $statusAt,
        private readonly MessageStatus $status,
        private readonly ?string $userReference,
        private readonly ?string $errorDescription,
        private readonly ?string $errorCode
    )
    {

        parent::__construct($eventId, $eventTimestamp, $eventType, $messageId, $phoneNumber);
    }

    /**
     * The ISO-8601 timestamp of the status change (`status_at`).
     */
    public function getStatusAt(): string
    {

        return $this->statusAt;
    }

    /**
     * The status change timestamp as a Unix timestamp, for backwards compatibility.
     */
    public function getTimestamp(): int
    {

        return (int)strtotime($this->statusAt);
    }

    public function getStatus(): MessageStatus
    {

        return $this->status;
    }

    public function getUserReference(): ?string
    {

        return $this->userReference;
    }

    /**
     * The error code (`hex_code`), if the event carries an error.
     */
    public function getErrorCode(): ?string
    {

        return $this->errorCode;
    }

    /**
     * The error description (`details`), if the event carries an error.
     */
    public function getErrorDescription(): ?string
    {

        return $this->errorDescription;
    }

    /**
     * @inheritDoc
     * @return DeliveryStatusWebhook
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
            || !array_key_exists('recipient', $event)
            || !array_key_exists('status', $event)
            || !array_key_exists('status_at', $event)) {

            throw new WebhookException(
                self::class . ' missing required event keys. Got: ' . implode(',', array_keys($event))
            );

        }

        $eventType = EventType::fromString($array['event_type']);

        $status = is_string($event['status'])
            ? MessageStatus::fromString($event['status'])
            : MessageStatus::UNKNOWN;

        $errorCode = null;
        $errorDescription = null;

        if (isset($event['error']) && is_array($event['error'])) {
            $errorCode = array_key_exists('hex_code', $event['error']) ? $event['error']['hex_code'] : null;
            $errorDescription = array_key_exists('details', $event['error']) ? $event['error']['details'] : null;
        }

        return new self(
            $array['event_id'],
            $array['timestamp'],
            $eventType,
            $event['msg_id'],
            $event['recipient'],
            $event['status_at'],
            $status,
            array_key_exists('reference', $event) ? $event['reference'] : null,
            $errorDescription,
            $errorCode
        );

    }
}
