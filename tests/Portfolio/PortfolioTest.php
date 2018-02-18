<?php

namespace TotalReturn\Portfolio;

use PHPUnit\Framework\TestCase;
use TotalReturn\App;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\MarketData;
use TotalReturn\Service;
use TotalReturn\Market\Symbol;
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
        $md->setLogger($logger = new Logger());

        $portfolio = new Portfolio(new \DateTime('2017-02-01'), $md);

        $portfolio->setLogger($logger);

        $portfolio->deposit($basis = 10000);
        $portfolio->buyAmount($aapl = Symbol::lookup('PTTRX'), $basis);
        $portfolio->forwardTo(new \DateTime('2018-02-01'));
        $portfolio->flatten($aapl);

        $value = $portfolio->getPosition(Symbol::lookup('$USD'));

        var_dump($value, $basis);

        var_dump($value / $basis);
    }
}
