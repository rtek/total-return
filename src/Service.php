<?php declare(strict_types=1);

namespace TotalReturn;

use Doctrine\DBAL\Connection;
use TotalReturn\Api\Av\Client as AvClient;
use TotalReturn\Api\Iex\Client as IexClient;
use TotalReturn\Api\Xignite\Client as XigniteClient;

class Service
{
    public const CONFIG = __NAMESPACE__.'\Config';
    public const ALPHAVANTAGE_CLIENT = AvClient::class;
    public const IEX_CLIENT = IexClient::class;
    public const MARKET_DATA = MarketData::class;
    public const DBAL_CONNECTION = Connection::class;
    public const KEY_VALUE = KeyValue::class;
    public const XIGNITE_CLIENT = XigniteClient::class;

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
