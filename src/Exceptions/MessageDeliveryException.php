<?php

declare(strict_types=1);

namespace nickdnk\GatewayAPI\Exceptions;

use nickdnk\GatewayAPI\Entities\Response\Result;

/**
 * Thrown by GatewayAPIHandler::deliverMessages() when one or more of the `/mobile/multi`
 * batches fail. Because messages are split into batches and delivered concurrently, some
 * batches may have succeeded before others failed — inspect {@see getPartialResult()} for
 * the messages that were accepted, and {@see getExceptions()} for the per-batch failures.
 *
 * @package nickdnk\GatewayAPI\Exceptions
 */
class MessageDeliveryException extends BaseException
{

    /**
     * @param BaseException[] $exceptions    One exception per failed batch (GatewayRequestException or ConnectionException).
     * @param Result          $partialResult The IDs of messages from batches that succeeded.
     */
    public function __construct(
        string $message,
        private readonly array $exceptions,
        private readonly Result $partialResult
    )
    {

        parent::__construct($message);
    }

    /**
     * The failures, one per failed batch. Each is a GatewayRequestException (the request
     * completed with an error response) or a ConnectionException (the request never
     * completed).
     *
     * @return BaseException[]
     */
    public function getExceptions(): array
    {

        return $this->exceptions;
    }

    /**
     * The aggregated result of the batches that succeeded. Its message IDs are those that
     * were accepted by the API despite the overall failure.
     */
    public function getPartialResult(): Result
    {

        return $this->partialResult;
    }

}
