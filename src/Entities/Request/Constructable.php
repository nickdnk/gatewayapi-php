<?php


namespace nickdnk\GatewayAPI\Entities\Request;

use JsonSerializable;

interface Constructable extends JsonSerializable
{

    /**
     * @param string $json
     *
     * @return Constructable
     */
    public static function constructFromJSON(string $json): Constructable;

}
