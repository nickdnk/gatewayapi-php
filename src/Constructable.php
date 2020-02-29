<?php


namespace nickdnk\GatewayAPI;

interface Constructable extends \JsonSerializable
{

    /**
     * @param string $json
     *
     * @return Constructable
     */
    public static function constructFromJSON(string $json): Constructable;

}