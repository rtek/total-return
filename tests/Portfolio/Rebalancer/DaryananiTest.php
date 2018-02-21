<?php


namespace TotalReturn\Portfolio\Rebalancer;


use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\Portfolio\Portfolio;

class DaryananiTest extends TestCase
{
    use AppTrait;

    public function testSimple()
    {
        $md = $this->getMarketData();
        $portfolio = new Portfolio(new \DateTime('2017-01-01'), $md);

        $logger = true ? new NullLogger() : new Logger();
        $md->setLogger($logger);
        $portfolio->setLogger($logger);

        $portfolio->setRebalancer(new Daryanani([
            'VTI'  => 0.35,
            'VXUS' => 0.35,
            'BND'  => 0.30,
        ], 1, 0.05, 0.025));

        $portfolio->deposit(10000);
        $portfolio->forwardTo(new \DateTime('2018-02-18'));

        $this->assertEquals(0, round($portfolio->getPosition($portfolio->getCashSymbol()),2));

        //todo test that allocation does not exceed rebalance bands
        //var_dump($portfolio->getAllocation());


    }
}
