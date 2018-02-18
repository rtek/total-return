<?php declare(strict_types=1);

namespace TotalReturn\Api;

class Attribute
{
    use HasJson;

    public function __construct(array $json = [])
    {
        $this->json = $json;
    }
}
