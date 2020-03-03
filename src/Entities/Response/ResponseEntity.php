<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use nickdnk\GatewayAPI\Exceptions\GatewayRequestException;
use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use Psr\Http\Message\ResponseInterface;

interface ResponseEntity
{

    /**
     * @param ResponseInterface $response
     *
     * @return ResponseEntity
     * @throws SuccessfulResponseParsingException
     * @throws GatewayRequestException
     */
    public static function constructFromResponse(ResponseInterface $response): ResponseEntity;

}
