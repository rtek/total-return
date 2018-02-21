<?php declare(strict_types=1);

namespace TotalReturn\Portfolio\Rebalancer;

use TotalReturn\Market\Symbol;
use TotalReturn\Portfolio\Portfolio;

class Manual extends AbstractRebalancer
{
    public function needsRebalance(Portfolio $portfolio): bool
    {
        return false;
    }

    public function calculateTrades(Portfolio $portfolio): array
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

        return $deltas;
    }
}
