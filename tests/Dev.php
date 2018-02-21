<?php declare(strict_types=1);

namespace TotalReturn;

use PHPUnit\Framework\TestCase;
use TotalReturn\Portfolio\Portfolio;

class Dev extends TestCase
{
    use AppTrait;

    public function testDev(): void
    {
        $md = $this->getMarketData();

        $md->setLogger($logger = new Logger());

        $portfolio = new Portfolio(new \DateTime('2017-01-01'), $md);
        $portfolio->setLogger($logger);

        $portfolio->setTargetAllocation([
            'VTI'  => 0.35,
            'VXUS' => 0.35,
            'VWIUX'  => 0.28,
            '$USD'  => 0.02,
        ]);

        $portfolio->deposit(10000);

        $portfolio->forwardTo(new \DateTime('2018-02-18'));

        var_dump($portfolio->getValue());
    }
}
