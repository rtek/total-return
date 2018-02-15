<?php

namespace TotalReturn\Portfolio;

use PHPUnit\Framework\TestCase;
use TotalReturn\App;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\MarketData;
use TotalReturn\Service;
use Zend\ConfigAggregator\PhpFileProvider;

class PortfolioTest extends TestCase
{
    use AppTrait;

    public function testTrades()
    {
        $app = $this->createApp();
        $sm = $app->getServiceManager();

        /** @var MarketData $md */
        $md = $sm->get(Service::MARKET_DATA);

        $portfolio = new Portfolio(new \DateTime('2018-01-02'), $md);

        $portfolio->setLogger(new Logger());

        $portfolio->deposit(100000);
        $portfolio->buy('AAPL', 100);
        $portfolio->forwardTo($md->getLastCloseDay());
        $portfolio->sell('AAPL', 100);

        var_dump($portfolio->getPosition(Portfolio::CASH));
    }
}
