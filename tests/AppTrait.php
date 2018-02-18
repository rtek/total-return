<?php declare(strict_types=1);

namespace TotalReturn;

use Zend\ConfigAggregator\PhpFileProvider;
use Zend\ServiceManager\ServiceManager;

trait AppTrait
{
    /** @var App */
    protected $app;

    protected function tearDown(): void
    {
        $this->app = null;
    }

    /**
     * @return App
     */
    protected function createApp(): App
    {
        return App::create([new PhpFileProvider('tests/_files/config/{,*.}{global,local}.php')]);
    }

    /**
     * @return App
     */
    protected function getApp(): App
    {
        if (!$this->app) {
            $this->app = $this->createApp();
        }
        return $this->app;
    }

    /**
     * @return ServiceManager
     */
    protected function getServiceManager(): ServiceManager
    {
        return $this->getApp()->getServiceManager();
    }

    /**
     * @return MarketData
     */
    protected function getMarketData(): MarketData
    {
        return $this->getServiceManager()->get(Service::MARKET_DATA);
    }
}
