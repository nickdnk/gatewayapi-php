<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Webhooks;

/**
 * The delivery status of a message, as reported by a `message.status.*` webhook event.
 *
 * @link https://gatewayapi.com/docs/message/overview/
 */
enum MessageStatus: string
{

    /**
     * Also used as the fallback for a status string this SDK version does not recognise.
     * If you receive UNKNOWN for a status you expect to be meaningful, the API may have
     * introduced a newer status — handle it gracefully and consider upgrading the SDK.
     */
    case UNKNOWN       = 'UNKNOWN';
    case EN_ROUTE      = 'ENROUTE';
    case DELIVERED     = 'DELIVERED';
    case EXPIRED       = 'EXPIRED';
    case DELETED       = 'DELETED';
    case UNDELIVERABLE = 'UNDELIVERABLE';
    case ACCEPTED      = 'ACCEPTED';
    case REJECTED      = 'REJECTED';
    // RCS-only status.
    case READ          = 'READ';

    /**
     * Resolves a raw status string, returning {@see MessageStatus::UNKNOWN} for any value
     * this SDK version does not recognise (rather than failing).
     */
    public static function fromString(string $value): MessageStatus
    {

        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    /**
     * Whether this is a terminal status (no further updates expected).
     */
    public function isFinal(): bool
    {

        return match ($this) {
            self::DELIVERED, self::EXPIRED, self::DELETED, self::UNDELIVERABLE, self::REJECTED => true,
            default                                                                            => false,
        };
    }

}
