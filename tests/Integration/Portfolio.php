<?php declare(strict_types=1);

namespace TotalReturn\Integration;

use PHPUnit\Framework\TestCase;
use TotalReturn\AppTrait;
use TotalReturn\Logger;
use TotalReturn\Portfolio\Rebalancer\Daryanani;
use TotalReturn\Portfolio\Portfolio as Folio;

class Portfolio extends TestCase
{
    use AppTrait;

    public function testTrades(): void
    {
        $md = $this->getMarketData();
        $portfolio = new Folio(new \DateTime('2013-01-01'), $md);

        $logger = new Logger();
        $md->setLogger($logger);
        $portfolio->setLogger($logger);



        $portfolio->getEvents()
            ->on(Folio::E_FORWARD, [$logger, 'debugAllocation']);
            //->on(Folio::E_BEFORE_REBALANCE, [$logger, 'debugAllocation'])
           // ->on(Folio::E_REBALANCE, [$logger, 'debugAllocation']);


        $portfolio->setRebalancer(new Daryanani($targetAlloc = [
            'VTI'  => 0.35,
            'VXUS' => 0.35,
            'BND'  => 0.30,
        ], 1, 0.20, 0.10));

        $portfolio->deposit(10000);
        $portfolio->forwardTo(new \DateTime('2018-03-10'));

        var_dump($portfolio->getValues(), $portfolio->getTotalValue());


    }
}
