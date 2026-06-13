<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Entities\Request;

use InvalidArgumentException;
use JsonSerializable;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class SMSMessage
 *
 * The internal (serialized) shape of this entity is preserved from previous versions
 * so messages stored in a queue as JSON can still be re-hydrated. When sent, each
 * message is translated to the messaging API's MobileMessageRequest format via
 * {@see SMSMessage::toMobileMessageRequests()}.
 *
 * Note: the messaging API has no concept of scheduled send time, per-message tags,
 * tag values, message class or encoding. These fields are still accepted and serialized
 * for backwards compatibility but are NOT transmitted. Per-message callback URLs are no
 * longer supported at all (webhooks are configured in the dashboard).
 *
 * @property string      $class
 * @property string      $message
 * @property string      $sender
 * @property Recipient[] $recipients
 * @property string[]    $tags
 * @property string|null $userref
 * @property string|null $encoding
 * @package nickdnk\GatewayAPI
 */
class SMSMessage implements JsonSerializable
{

    use Constructable;

    const CLASS_STANDARD = 'standard';
    const CLASS_PREMIUM  = 'premium';
    const CLASS_SECRET   = 'secret';

    /**
     * Passing `UTF8` as encoding means the message will use the GSM-7 character set. To use emojis etc., use
     * `SMSMessage::ENCODING_UCS2`.
     * @link https://gatewayapi.com/docs/glossary/#gsm-7
     */
    const ENCODING_UTF8 = 'UTF8';
    /**
     * Passing `UCS2` as encoding means the message will use the UCS2 character set, which allows for emojis but
     * increases message length.
     * @link https://gatewayapi.com/docs/glossary/#ucs-2
     */
    const ENCODING_UCS2 = 'UCS2';

    private string $message;
    private string $sender;
    /** @var Recipient[] */
    private array $recipients;
    /** @var string[] */
    private array $tags;
    private string $class;
    private ?string $userref;
    private ?string $encoding;

    /**
     * @throws InvalidArgumentException If required keys are missing or of the wrong type.
     */
    public static function constructFromArray(array $array): static
    {

        if (array_key_exists('class', $array)
            && array_key_exists('message', $array)
            && array_key_exists('sender', $array)
            && array_key_exists('recipients', $array)
            && array_key_exists('tags', $array)
            && is_string($array['class'])
            && is_string($array['message'])
            && is_string($array['sender'])
            && is_array($array['recipients'])
            && is_array($array['tags'])) {

            $recipients = [];

            foreach ($array['recipients'] as $recipient) {

                $recipients[] = Recipient::constructFromArray($recipient);

            }

            return new self(
                $array['message'],
                $array['sender'],
                $recipients,
                array_key_exists('userref', $array) ? $array['userref'] : null,
                $array['tags'],
                $array['class'],
                array_key_exists('encoding', $array) ? $array['encoding'] : null
            );

        }

        throw new InvalidArgumentException('Array passed to ' . self::class . ' is missing required parameters.');

    }

    /**
     * SMSMessage constructor.
     *
     * @param string      $message
     * @param string      $senderName
     * @param Recipient[] $recipients
     * @param string|null $userReference
     * @param string[]    $tags
     * @param string      $class
     * @param string|null $encoding
     *
     * @throws InvalidArgumentException If $class or $encoding is not a valid value.
     */
    public function __construct(string $message, string $senderName, array $recipients = [],
        ?string $userReference = null, array $tags = [], string $class = self::CLASS_STANDARD,
        ?string $encoding = null
    )
    {

        $this->message = $message;
        $this->sender = $senderName;
        $this->recipients = $recipients;
        $this->userref = $userReference;
        $this->tags = $tags;
        $this->setEncoding($encoding);
        $this->setClass($class);

    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {

        $this->tags = $tags;
    }


    /**
     * Must be one of the available constants; `standard`, `premium` or `secret`. Use the built-in constants provided
     * by this class, i.e: `SMSMessage::CLASS_STANDARD`.
     *
     * @param string $class
     *
     * @throws InvalidArgumentException If $class is not one of the CLASS_* constants.
     */
    public function setClass(string $class): void
    {

        if ($class !== self::CLASS_STANDARD
            && $class !== self::CLASS_PREMIUM
            && $class !== self::CLASS_SECRET) {
            throw new InvalidArgumentException(
                'SMS class must be one of the provided constants. Received value: ' . $class
            );
        }

        $this->class = $class;

    }

    /**
     * @throws InvalidArgumentException If $encoding is not an ENCODING_* constant or null.
     */
    public function setEncoding(?string $encoding): void
    {

        if ($encoding !== self::ENCODING_UTF8
            && $encoding !== self::ENCODING_UCS2
            && $encoding !== null) {
            throw new InvalidArgumentException(
                'SMS encoding must be one of the provided constants or null. Received value: ' . $encoding
            );
        }

        $this->encoding = $encoding;

    }

    public function getClass(): string
    {

        return $this->class;
    }

    public function getMessage(): string
    {

        return $this->message;
    }

    public function getSender(): string
    {

        return $this->sender;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {

        return $this->tags;
    }

    public function getUserReference(): ?string
    {

        return $this->userref;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function setUserReference(string $userReference): void
    {

        $this->userref = $userReference;
    }

    /**
     * Returns all the recipients that have been added to the SMS message.
     *
     * @return Recipient[]
     */
    public function getRecipients(): array
    {

        return $this->recipients;
    }

    /**
     * Adds a single recipient to the message.
     *
     * @param Recipient $recipient
     */
    public function addRecipient(Recipient $recipient): void
    {

        $this->recipients[] = $recipient;
    }

    /**
     * Sets the recipient array, overriding any existing recipients of the message.
     *
     * @param Recipient[] $recipients
     */
    public function setRecipients(array $recipients): void
    {

        $this->recipients = $recipients;

    }

    /**
     * Translates this message into one or more MobileMessageRequest payloads for the
     * messaging API. The messaging API accepts a single recipient per message, so a
     * message with N recipients yields N payloads. The user reference is mapped to the
     * messaging API's `reference` field.
     *
     * @return array[]
     */
    public function toMobileMessageRequests(): array
    {

        $requests = [];

        foreach ($this->recipients as $recipient) {

            $request = [
                'sender'    => $this->sender,
                'recipient' => $recipient->getMsisdn(),
                'message'   => $this->message
            ];

            if ($this->userref !== null) {
                $request['reference'] = $this->userref;
            }

            $requests[] = $request;

        }

        return $requests;

    }

    public function jsonSerialize(): array
    {

        $json = [
            'class'      => $this->class,
            'message'    => $this->message,
            'sender'     => $this->sender,
            'recipients' => $this->recipients,
            'tags'       => $this->tags
        ];

        if ($this->userref !== null) {
            $json['userref'] = $this->userref;
        }

        if ($this->encoding !== null) {
            $json['encoding'] = $this->encoding;
        }

        return $json;
    }


}
