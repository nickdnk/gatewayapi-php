<?php


namespace nickdnk\GatewayAPI\Exceptions;

class WebhookException extends BaseException
{

    /**
     * WebhookException constructor.
     *
     * This exception is thrown whenever something is wrong with an incoming webhook, such as a wrong JWT secret.
     *
     * @param string $message
     */
    public function __construct(string $message)
    {

        parent::__construct($message, null, null);
    }
}