<?php


namespace nickdnk\GatewayAPI\Entities;

use nickdnk\GatewayAPI\Exceptions\BaseException;

final class CancelResult
{

    const STATUS_FAILED    = 'failed';
    const STATUS_SUCCEEDED = 'succeeded';

    private string $status = self::STATUS_SUCCEEDED;
    private ?BaseException $exception = null;


    public function __construct(private readonly int $messageId)
    {
    }

    public function getMessageId(): int
    {

        return $this->messageId;
    }


    public function getStatus(): string
    {

        return $this->status;
    }

    public function getException(): ?BaseException
    {

        return $this->exception;
    }

    public function setStatus(string $status): void
    {

        $this->status = $status;
    }


    public function setException(BaseException $exception): void
    {

        $this->exception = $exception;
    }


}
