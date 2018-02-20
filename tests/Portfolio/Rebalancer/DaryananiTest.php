<?php


namespace TotalReturn\Portfolio\Rebalancer;


use PHPUnit\Framework\TestCase;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\Portfolio\Portfolio;

class DaryananiTest extends TestCase
{
    use AppTrait;

    public function testSimple()
    {
        $md = $this->getMarketData();
        $md->setLogger($logger = new Logger());

        $portfolio = new Portfolio(new \DateTime('2017-01-01'), $md);
        $portfolio->setLogger($logger = new Logger());

        $portfolio->setRebalancer(new Daryanani([
            'VTI'  => 0.35,
            'VXUS' => 0.35,
            'BND'  => 0.28,
        ], 1, 0.05, 0.025));

        $portfolio->deposit(10000);
        $portfolio->forwardTo(new \DateTime('2018-02-18'));

        var_dump($portfolio->getPosition($portfolio->getCashSymbol()));

    }
}
