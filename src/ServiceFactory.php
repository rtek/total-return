<?php

namespace TotalReturn;

use Doctrine\DBAL\DriverManager;

use Interop\Container\ContainerInterface;
use TotalReturn\Api\Av\Client as AvClient;
use TotalReturn\Api\Iex\Client as IexClient;
use TotalReturn\Api\Xignite\Client as XigniteClient;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    /** @var ContainerInterface */
    protected $ct;

    public function __invoke(ContainerInterface $ct, $name, array $opts = null)
    {
        $this->ct = $ct;

        switch($name) {
            case Service::ALPHAVANTAGE_CLIENT:
                return new AvClient($this->getConfig('av', 'key'));
            case Service::IEX_CLIENT:
                return new IexClient();
            case Service::XIGNITE_CLIENT:
                $cfg = $this->getConfig('xignite');
                return new XigniteClient($cfg['token'], $cfg['user_id']);
            case Service::MARKET_DATA:
                return new MarketData(
                    $ct->get(Service::KEY_VALUE),
                    $ct->get(Service::ALPHAVANTAGE_CLIENT),
                    $ct->get(Service::IEX_CLIENT),
                    $ct->get(Service::XIGNITE_CLIENT)
                );
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

        throw new ServiceNotFoundException("Could not resolve service '$name'");
    }

    protected function getConfig(...$keys)
    {
        $lookup = $this->ct->get(Service::CONFIG);
        foreach ($keys as $key) {
            if(!is_array($lookup)) {
                break;
            }
            if(!array_key_exists($key, $lookup)) {
                $keys = implode(', ', array_keys($lookup));
                throw new \RuntimeException("Could not find config $key from keys $keys");
            }
            $lookup = $lookup[$key];
        }
        return $lookup;
    }
}
