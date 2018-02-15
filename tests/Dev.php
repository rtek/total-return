<?php

namespace TotalReturn;

use Flintstone\Flintstone;
use PHPUnit\Framework\TestCase;
use TotalReturn\Av\Client;
use Zend\ConfigAggregator\PhpFileProvider;

class Dev extends TestCase
{
    public function _testDev()
    {
        $app = App::create([new PhpFileProvider('tests/_files/config/{,*.}{global,local}.php')]);

        $sm = $app->getServiceManager();

        /** @var Client $client */
        $client = $sm->get(Service::ALPHAVANTAGE_CLIENT);

        /** @var Flintstone $fs */
        $fs = $sm->get(Service::FLINTSTONE);

        $data = $client->getDaily('INTC');

        foreach($data['Time Series (Daily)'] as $date => $datum) {
            var_dump($date, $datum);
            $fs->set($date, $datum);
        }

    }
}
