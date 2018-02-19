<?php


namespace TotalReturn\Portfolio\Rebalancer;


use TotalReturn\Market\Symbol;
use TotalReturn\Portfolio\Portfolio;

class Manual implements RebalancerInterface
{
    /** @var array */
    protected $allocation;

    public function __construct(array $allocation)
    {
        $this->setAllocation($allocation);
    }

    public function setAllocation(array $allocation)
    {
        $this->allocation = $allocation;
        $total = array_sum($this->allocation);
        if ($total > 1 || $total <= 0) {
           throw new \RuntimeException('Allocation must sum to (0..1]');
        }
    }

    public function needsRebalance(Portfolio $portfolio): bool
    {
        return false;
    }

    public function rebalance(Portfolio $portfolio): void
    {
        $values = $portfolio->getValues();
        $total = array_sum($values);
        $deltas = [];

        foreach ($this->allocation as $ticker => $target) {
            //skip cash
            if ($ticker === Symbol::TICKER_USD) {
                continue;
            }

            $deltas[$ticker] = $target * $total - ($values[$ticker] ?? 0);
        }

        //sell then buy
        asort($deltas);
        foreach ($deltas as $ticker => $delta) {
            $portfolio->tradeAmount(Symbol::lookup($ticker), $delta);
        }
    }

}
