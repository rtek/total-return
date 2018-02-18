<?php

namespace TotalReturn\Api;


class Endpoint extends Attribute
{
    final public function __construct(array $json = [])
    {
        parent::__construct($json);
    }
}
