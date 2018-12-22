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
        $portfolio = new Folio(new \DateTime('2018-01-31'), $md);

        $logger = new Logger();
        $md->setLogger($logger);
        $portfolio->setLogger($logger);



        $portfolio->getEvents()
            //->on(Folio::E_FORWARD, [$logger, 'debugAllocation']);
            ->on(Folio::E_BEFORE_REBALANCE, [$logger, 'debugAllocation'])
            ->on(Folio::E_REBALANCE, [$logger, 'debugAllocation']);


        $portfolio->setRebalancer(new Daryanani($targetAlloc = [
            'VTI'  => 0.325,
            'VXUS' => 0.325,
            'BND'  => 0.35,
        ], 1, 0.10, 0.05));

        $portfolio->deposit($deposit = 10000);
        $portfolio->forwardTo(new \DateTime('today'));

        var_dump($portfolio->getValues(), $portfolio->getTotalValue());

        $logger->info(sprintf(
            "Report\n Total Return: %%%.2f\n",
            ($portfolio->getTotalValue() - $deposit) / $deposit * 100
        ));


    }
}
