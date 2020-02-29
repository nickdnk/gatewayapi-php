<?php


namespace nickdnk\GatewayAPI;

/**
 * Class SMSMessage
 *
 * @property string      $class
 * @property string      $message
 * @property string      $sender
 * @property Recipient[] $recipients
 * @property string[]    $tags
 * @property int         $sendtime
 * @property string      $userref
 * @package nickdnk\GatewayAPI
 */
class SMSMessage implements Constructable
{

    const CLASS_STANDARD = 'standard';
    const CLASS_PREMIUM  = 'premium';
    const CLASS_SECRET   = 'secret';

    private $message, $sender, $recipients, $tags, $sendtime, $class, $userref;

    /**
     * @param string $json
     *
     * @return SMSMessage
     */
    public static function constructFromJSON(string $json): Constructable
    {

        $array = json_decode($json, true);

        if (!$array) {
            throw new \InvalidArgumentException('Invalid JSON passed to SMSMessage.');
        }

        if (array_key_exists('class', $array)
            && array_key_exists('message', $array)
            && array_key_exists('sender', $array)
            && array_key_exists('recipients', $array)
            && array_key_exists('tags', $array)) {

            $recipients = [];

            foreach ($array['recipients'] as $recipient) {

                $recipients[] = new Recipient($recipient['msisdn'], $recipient['tagvalues']);

            }

            return new self(
                $array['message'],
                $array['sender'],
                $recipients,
                array_key_exists('userref', $array) ? $array['userref'] : null,
                $array['tags'],
                array_key_exists('sendtime', $array) ? $array['sendtime'] : null,
                $array['class']
            );

        } else {

            throw new \InvalidArgumentException('JSON passed to SMSMessage is missing required parameters.');

        }

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
     */
    public function __construct(string $message, string $senderName, array $recipients = [],
        ?string $userReference = null, array $tags = [], ?int $sendTime = null, string $class = self::CLASS_STANDARD
    )
    {

        $this->message = $message;
        $this->sender = $senderName;
        $this->recipients = $recipients;
        $this->userref = $userReference;
        $this->tags = $tags;
        $this->sendtime = $sendTime;
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
     * Must be one of the available constants; standard, premium or secret.
     *
     * @param  $class
     */
    public function setClass(string $class): void
    {

        if ($class !== self::CLASS_STANDARD
            && $class !== self::CLASS_PREMIUM
            && $class !== self::CLASS_SECRET) {
            throw new \InvalidArgumentException(
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
     * @return int
     */
    public function getSendtime(): int
    {

        return $this->sendtime;
    }

    /**
     * @return string
     */
    public function getUserref(): string
    {

        return $this->userref;
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

    public function addRecipient(Recipient $recipient)
    {

        $this->recipients[] = $recipient;
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

        return $json;
    }

}