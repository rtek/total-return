<?php

namespace TotalReturn;

use Zend\ServiceManager\ServiceManager;

class Service
{
    const CONFIG = 'TotalReturn\Config';


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
