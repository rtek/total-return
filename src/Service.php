<?php

namespace TotalReturn;

use Doctrine\DBAL\Connection;
use TotalReturn\Av\Client as AvClient;
use TotalReturn\Iex\Client as IexClient;

class Service
{
    const CONFIG = __NAMESPACE__.'\Config';
    const ALPHAVANTAGE_CLIENT = AvClient::class;
    const IEX_CLIENT = IexClient::class;
    const MARKET_DATA = MarketData::class;
    const DBAL_CONNECTION = Connection::class;
    const KEY_VALUE = KeyValue::class;

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
