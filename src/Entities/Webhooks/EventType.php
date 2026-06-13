<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Webhooks;

/**
 * The webhook event types emitted by the messaging API.
 *
 * @link https://gatewayapi.com/docs/message/overview/#webhook-callbacks
 */
enum EventType: string
{

    case STATUS_SMS        = 'message.status.sms';
    case STATUS_RCS        = 'message.status.rcs';
    case USER_TEXT_SMS     = 'user-message.text.sms';
    case USER_TEXT_RCS     = 'user-message.text.rcs';
    case USER_LOCATION_RCS = 'user-message.location.rcs';
    case USER_FILE_RCS     = 'user-message.file.rcs';

    /**
     * Fallback for an `event_type` this SDK version does not recognise. Encountering it
     * means the API emitted a newer event type — handle it gracefully and consider
     * upgrading the SDK.
     */
    case UNKNOWN = 'unknown';

    /**
     * Resolves a raw `event_type` string, returning {@see EventType::UNKNOWN} for any
     * value this SDK version does not recognise (rather than failing).
     */
    public static function fromString(string $value): EventType
    {

        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    /**
     * Whether this is a recognised event type (i.e. not {@see EventType::UNKNOWN}).
     */
    public function isKnown(): bool
    {

        return $this !== self::UNKNOWN;
    }

    /**
     * Whether this event is a delivery-status update (vs. an inbound user message).
     */
    public function isDeliveryStatus(): bool
    {

        return match ($this) {
            self::STATUS_SMS, self::STATUS_RCS => true,
            default                            => false,
        };
    }

    /**
     * Whether this event is an inbound message from a user.
     */
    public function isIncomingMessage(): bool
    {

        return match ($this) {
            self::USER_TEXT_SMS, self::USER_TEXT_RCS, self::USER_LOCATION_RCS, self::USER_FILE_RCS => true,
            default                                                                                => false,
        };
    }

}
