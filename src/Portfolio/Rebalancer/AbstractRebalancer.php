<?php declare(strict_types=1);

namespace TotalReturn\Portfolio\Rebalancer;

use TotalReturn\Market\Symbol;
use TotalReturn\Portfolio\Portfolio;

abstract class AbstractRebalancer implements RebalancerInterface
{
    /** @var array */
    protected $allocation;

    /** @var Portfolio */
    protected $portfolio;

    public function __construct(array $allocation)
    {
        $this->setAllocation($allocation);
    }

    public function setPortfolio(Portfolio $portfolio): void
    {
        $this->portfolio = $portfolio;
    }

    public function setAllocation(array $allocation): void
    {
        $this->allocation = $allocation;
        $total = array_sum($this->allocation);
        if ($total > 1 || $total <= 0) {
            throw new \RuntimeException('Allocation must sum to (0..1]');
        }
    }

    public function rebalance(): void
    {
        $trades = $this->calculateTrades();
        asort($trades);
        foreach ($trades as $ticker => $trade) {
            $this->portfolio->tradeAmount(Symbol::lookup($ticker), $trade);
        }
    }

    abstract public function calculateTrades(): array;

    protected function flattenOthers(array $values): array
    {
        $trades = [];
        foreach ($values as $ticker => $value) {
            if (!array_key_exists($ticker, $this->allocation)) {
                $trades[$ticker] = -$value;
            }
        }
        return $trades;
    }
}
