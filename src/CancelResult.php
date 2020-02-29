<?php


namespace nickdnk\GatewayAPI;

use nickdnk\GatewayAPI\Exceptions\BaseException;

class CancelResult
{

    const STATUS_FAILED    = 'failed';
    const STATUS_SUCCEEDED = 'succeeded';

    private $status, $exception, $messageId;


    public function __construct(int $messageId)
    {

        $this->status = self::STATUS_SUCCEEDED;
        $this->exception = null;
        $this->messageId = $messageId;
    }

    /**
     * @return int
     */
    public function getMessageId(): int
    {

        return $this->messageId;
    }


    /**
     * @return string
     */
    public function getStatus(): string
    {

        return $this->status;
    }

    /**
     * @return BaseException|null
     */
    public function getException(): ?BaseException
    {

        return $this->exception;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {

        $this->status = $status;
    }


    /**
     * @param BaseException $exception
     */
    public function setException(BaseException $exception): void
    {

        $this->exception = $exception;
    }


}