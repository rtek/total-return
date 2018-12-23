<?php declare(strict_types=1);

namespace TotalReturn\Portfolio\Rebalancer;

use TotalReturn\Market\Symbol;

class Manual extends AbstractRebalancer
{
    public function needsRebalance(): bool
    {
        return false;
    }

    public function calculateTrades(): array
    {
        $values = $this->portfolio->getValues();
        $total = array_sum($values);
        $deltas = [];

        foreach ($this->allocation as $ticker => $target) {
            //skip cash
            if ($ticker === Symbol::TICKER_USD) {
                continue;
            }

            $deltas[$ticker] = $target * $total - ($values[$ticker] ?? 0);
        }

        return $deltas;
    }
}
