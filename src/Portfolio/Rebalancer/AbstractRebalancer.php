<?php

namespace TotalReturn\Portfolio\Rebalancer;

use TotalReturn\Market\Symbol;
use TotalReturn\Portfolio\Portfolio;

abstract class AbstractRebalancer implements RebalancerInterface
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

    public function rebalance(Portfolio $portfolio): void
    {
        $trades = $this->calculateTrades($portfolio);
        asort($trades);
        foreach ($trades as $ticker => $trade) {
            $portfolio->tradeAmount(Symbol::lookup($ticker), $trade);
        }
    }

    abstract public function calculateTrades(Portfolio $portfolio): array;
}
