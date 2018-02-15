<?php

namespace TotalReturn;

use TotalReturn\Av\Client as AvClient;

class Service
{
    const CONFIG = __NAMESPACE__.'\Config';
    const ALPHAVANTAGE_CLIENT = AvClient::class;
    const MARKET_DATA = MarketData::class;


    public function __invoke()
    {
        return [
            'service_manager' => [
                'factories' => $this->getFactories(),
            ],
        ];
    }

    protected function getFactories()
    {
        $factory = new ServiceFactory();
        $consts = (new \ReflectionClass($this))->getConstants();
        return array_fill_keys($consts, $factory);
    }
}
