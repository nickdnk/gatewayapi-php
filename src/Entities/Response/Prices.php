<?php


namespace nickdnk\GatewayAPI\Entities\Response;

use nickdnk\GatewayAPI\Exceptions\SuccessfulResponseParsingException;
use nickdnk\GatewayAPI\ResponseParser;
use Psr\Http\Message\ResponseInterface;

class Prices implements ResponseEntity
{

    private $standard, $premium;


    public function __construct(array $standard, array $premium)
    {

        $this->standard = $standard;
        $this->premium = $premium;
    }

    /**
     * @return array
     */
    public function getStandard(): array
    {

        return $this->standard;
    }

    /**
     * @return array
     */
    public function getPremium(): array
    {

        return $this->premium;
    }


    /**
     * @inheritDoc
     * @return Prices
     */
    public static function constructFromResponse(ResponseInterface $response): ResponseEntity
    {

        $json = ResponseParser::jsonDecodeResponse($response);

        if (is_array($json)
            && isset($json['standard'])
            && isset($json['premium'])
            && is_array($json['standard'])
            && is_array($json['premium'])) {

            return new self($json['standard'], $json['premium']);

        }

        throw new SuccessfulResponseParsingException('Failed to parse Prices from: ' . json_encode($json), $response);

    }
}
