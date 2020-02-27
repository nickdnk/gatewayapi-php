<?php


namespace nickdnk\GatewayAPI;

interface Constructable
{

    /**
     * @param array|\stdClass $array
     *
     * @return static
     */
    public static function constructFromArray($array): self;

}