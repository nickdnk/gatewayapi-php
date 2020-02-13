<?php


namespace nickdnk\GatewayAPI\Exceptions;

class WebhookException extends BaseException
{


    /**
     * WebhookException constructor.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {

        parent::__construct($message, null, null);
    }
}