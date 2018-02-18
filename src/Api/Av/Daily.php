<?php


namespace TotalReturn\Api\Av;


use TotalReturn\Api\Attribute;

class Daily extends Attribute
{
    public function getClose(): float
    {
        return (float)$this->get('4. close');
    }
}
