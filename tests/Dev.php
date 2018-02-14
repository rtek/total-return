<?php

namespace TotalReturn;

use PHPUnit\Framework\TestCase;
use TotalReturn\Av\Client;
use Zend\ConfigAggregator\PhpFileProvider;

class Dev extends TestCase
{
    public function testConfig()
    {
        $app = App::create([new PhpFileProvider('tests/_files/config/{,*.}{global,local}.php')]);

        $sm = $app->getServiceManager();

        $client = new Client($sm->get(Service::CONFIG)['av']['key']);

        $client->getDaily('INTC'));
        var_dump($client->getDailyAdjusted('INTC'));

    }
}
