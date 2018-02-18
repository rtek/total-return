<?php declare(strict_types=1);

namespace TotalReturn\Portfolio;

use PHPUnit\Framework\TestCase;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\Market\Symbol;
use TotalReturn\MarketData;
use TotalReturn\Service;

class PortfolioTest extends TestCase
{
    use AppTrait;

    public function testDrip(): void
    {
        $app = $this->createApp();
        $sm = $app->getServiceManager();

        /** @var MarketData $md */
        $md = $sm->get(Service::MARKET_DATA);
        $md->setLogger($logger = new Logger());

        $portfolio = new Portfolio(new \DateTime('2015-02-05'), $md);

        $portfolio->deposit($basis = 10000);
        $portfolio->buyAmount($intc = Symbol::lookup('INTC'), $basis);
        $portfolio->forwardTo(new \DateTime('2018-02-18'));
        $portfolio->flatten($intc);

        //
        $this->assertEquals(14680.00, round($portfolio->getValue(),-1));
    }
}
