<?php


namespace nickdnk\GatewayAPI\Entities\Request;

use InvalidArgumentException;
use JsonSerializable;
use nickdnk\GatewayAPI\Entities\Constructable;

/**
 * Class SMSMessage
 *
 * @property string      $class
 * @property string      $message
 * @property string      $sender
 * @property Recipient[] $recipients
 * @property string[]    $tags
 * @property int|null    $sendtime
 * @property string|null $userref
 * @property string|null $callback_url
 * @package nickdnk\GatewayAPI
 */
class SMSMessage implements JsonSerializable
{

    use Constructable;

    const CLASS_STANDARD = 'standard';
    const CLASS_PREMIUM  = 'premium';
    const CLASS_SECRET   = 'secret';

    private $message, $sender, $recipients, $tags, $sendtime, $class, $userref, $callbackUrl;

    /**
     * @inheritDoc
     * @return SMSMessage
     */
    public static function constructFromArray(array $array)
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
                array_key_exists('sendtime', $array) ? $array['sendtime'] : null,
                $array['class'],
                array_key_exists('callback_url', $array) ? $array['callback_url'] : null
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
     * @param int|null    $sendTime
     * @param string      $class
     * @param string|null $callbackUrl
     */
    public function __construct(string $message, string $senderName, array $recipients = [],
        ?string $userReference = null, array $tags = [], ?int $sendTime = null, string $class = self::CLASS_STANDARD,
        ?string $callbackUrl = null
    )
    {

        $this->message = $message;
        $this->sender = $senderName;
        $this->recipients = $recipients;
        $this->userref = $userReference;
        $this->tags = $tags;
        $this->sendtime = $sendTime;
        $this->callbackUrl = $callbackUrl;
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
     * @return string
     */
    public function getClass(): string
    {

        return $this->class;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {

        return $this->message;
    }

    /**
     * @return string
     */
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

    /**
     * @return int|null
     */
    public function getSendtime(): ?int
    {

        return $this->sendtime;
    }

    /**
     * @return string|null
     */
    public function getUserReference(): ?string
    {

        return $this->userref;
    }

    /**
     * @return string|null
     */
    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    /**
     * @param int $sendTime
     */
    public function setSendTime(int $sendTime): void
    {

        $this->sendtime = $sendTime;
    }

    /**
     * @param string $userReference
     */
    public function setUserReference(string $userReference): void
    {

        $this->userref = $userReference;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl(string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * Sets the send-time of the message to null. Messages with no send time are sent immediately.
     */
    public function removeSendTime()
    {

        $this->sendtime = null;
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
    public function addRecipient(Recipient $recipient)
    {

        $this->recipients[] = $recipient;
    }

    /**
     * Sets the recipient array, overriding any existing recipients of the message.
     *
     * @param Recipient[] $recipients
     */
    public function setRecipients(array $recipients)
    {

        $this->recipients = $recipients;

    }

    public function jsonSerialize()
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

        if ($this->sendtime !== null) {
            $json['sendtime'] = $this->sendtime;
        }

        if ($this->callbackUrl !== null) {
            $json['callback_url'] = $this->callbackUrl;
        }

        return $json;
    }


}
