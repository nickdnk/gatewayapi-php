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
class SMSMessage implements \JsonSerializable
{

    const CLASS_STANDARD = 'standard';
    const CLASS_PREMIUM  = 'premium';
    const CLASS_SECRET   = 'secret';

    private $message, $sender, $recipients, $tags, $sendtime, $class, $userref;

    public function __construct(
        string $message, string $senderName, array $recipients = [], ?string $userReference = null, array $tags = [], ?int $sendTime = null, string $class = self::CLASS_STANDARD
    ) {

        $this->message = $message;
        $this->sender = $senderName;
        $this->recipients = $recipients;
        $this->userref = $userReference;
        $this->tags = $tags;
        $this->sendtime = $sendTime;
        $this->class = $class;

    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {

        $this->tags = $tags;
    }


    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {

        $this->class = $class;
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