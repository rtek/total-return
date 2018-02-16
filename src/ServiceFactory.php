<?php

namespace TotalReturn;

use Doctrine\DBAL\DriverManager;
use Interop\Container\ContainerInterface;
use TotalReturn\Av\Client as AvClient;
use TotalReturn\Iex\Client as IexClient;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $ct, $name, array $opts = null)
    {
        switch($name) {
            case Service::ALPHAVANTAGE_CLIENT:
                return new AvClient($ct->get(Service::CONFIG)['av']['key']);
            case Service::IEX_CLIENT:
                return new IexClient();
            case Service::MARKET_DATA:
                return new MarketData($ct->get(Service::KEY_VALUE), $ct->get(Service::ALPHAVANTAGE_CLIENT), $ct->get(Service::IEX_CLIENT));
            case Service::DBAL_CONNECTION:
                return DriverManager::getConnection([
                    'driver' => 'pdo_mysql',
                    'host' => '127.0.0.1',
                    'user' => 'root',
                    'password' => 'pass',
                    'dbname' => 'total_return'
                ]);
            case Service::KEY_VALUE:
                return new KeyValue($ct->get(Service::DBAL_CONNECTION));

        }
    }
}
