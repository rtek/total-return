<?php declare(strict_types=1);

namespace TotalReturn\Integration;

use PHPUnit\Framework\TestCase;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\Market\Symbol;
use TotalReturn\MarketData;
use TotalReturn\Service;

class Portfolio extends TestCase
{
    use AppTrait;

    public function testTrades(): void
    {
        $app = $this->createApp();
        $sm = $app->getServiceManager();

        /** @var MarketData $md */
        $md = $sm->get(Service::MARKET_DATA);
        $md->setLogger($logger = new Logger());

        $portfolio = new \TotalReturn\Portfolio\Portfolio(new \DateTime('2015-02-05'), $md);

        $portfolio->setLogger($logger);

        $portfolio->deposit($basis = 10000);
        $portfolio->buyAmount($intc = Symbol::lookup('INTC'), $basis);
        $portfolio->forwardTo(new \DateTime('today'));
        $portfolio->flatten($intc);

        $logger->info('Ending Value: '. $portfolio->getValue());
    }
}
