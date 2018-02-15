<?php

namespace TotalReturn;

use Flintstone\Flintstone;
use Interop\Container\ContainerInterface;
use TotalReturn\Av\Client as AvClient;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $ct, $name, array $opts = null)
    {
        switch($name) {
            case Service::ALPHAVANTAGE_CLIENT:
                return new AvClient($ct->get(Service::CONFIG)['av']['key']);
            case Service::MARKET_DATA:
                return new MarketData($ct->get(Service::ALPHAVANTAGE_CLIENT));
        }
    }
}
