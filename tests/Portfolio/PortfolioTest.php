<?php declare(strict_types=1);

namespace TotalReturn\Portfolio;

use PHPUnit\Framework\TestCase;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\Market\Symbol;

class PortfolioTest extends TestCase
{
    use AppTrait;

    public function testDrip(): void
    {
        $portfolio = new Portfolio(new \DateTime('2015-02-05'), $this->getMarketData());

        $portfolio->deposit($basis = 10000);
        $portfolio->buyAmount($intc = Symbol::lookup('INTC'), $basis);
        $portfolio->forwardTo(new \DateTime('2018-02-18'));
        $portfolio->flatten($intc);

        //total return calcs make different assumptions about when the divs are reinvested
        $this->assertEquals(14680.00, round($portfolio->getValue(), -1));
    }

    public function testSplits(): void
    {
        $portfolio = new Portfolio(new \DateTime('2015-02-05'), $md = $this->getMarketData());

        $portfolio->deposit($basis = 10000);
        $portfolio->buyAmount($vxx = Symbol::lookup('VXX'), $basis);
        $portfolio->forwardTo(new \DateTime('2018-02-18'));
        $portfolio->flatten($vxx);

        $this->assertEquals(800.00, round($portfolio->getValue(), -1));
    }

    public function testRebalance(): void
    {
        $md = $this->getMarketData();

        $portfolio = new Portfolio(new \DateTime('2018-01-30'), $md);

        $portfolio->setTargetAllocation([
            'VTI'  => 0.35,
            'VXUS' => 0.35,
            'BND'  => 0.28,
            '$USD'  => 0.02,
        ]);

        $portfolio->deposit(10000);

        $portfolio->forwardTo(new \DateTime('2018-02-18'));

        $this->assertEquals(9760.00, round($portfolio->getValue(), -1));
    }
}
