<?php

namespace TotalReturn;

use PHPUnit\Framework\TestCase;
use Zend\ConfigAggregator\PhpFileProvider;

class Dev extends TestCase
{
    public function testDev()
    {
        $app = App::create([new PhpFileProvider('tests/_files/config/{,*.}{global,local}.php')]);

        $sm = $app->getServiceManager();

        /** @var KeyValue $kv */
        $kv = $sm->get(Service::KEY_VALUE);

        $kv->set('ns','id','ehehehe');

    }
}
