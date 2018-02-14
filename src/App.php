<?php


namespace TotalReturn;

use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\PhpFileProvider;
use Zend\ServiceManager\ServiceManager;

class App
{
    /** @var ServiceManager */
    protected $serviceManager;

    public function __construct(ServiceManager $sm)
    {
        $this->serviceManager = $sm;
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager(): ServiceManager
    {
        return $this->serviceManager;
    }


    static public function create(array $configProviders = [])
    {
        array_unshift($configProviders, new PhpFileProvider('config/{,*.}{global,local}.php'));
        $config = (new ConfigAggregator($configProviders))->getMergedConfig();

        $sm = new ServiceManager($config['service_manager'] ?? []);
        $sm->setService(Service::CONFIG, $config);

        return new self($sm);
    }
}
