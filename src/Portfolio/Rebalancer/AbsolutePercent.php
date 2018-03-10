<?php declare(strict_types=1);

namespace TotalReturn\Portfolio\Rebalancer;

use TotalReturn\Market\Symbol;

class AbsolutePercent extends Manual
{
    /** @var float */
    protected $threshold;

    public function __construct(array $allocation, float $threshold)
    {
        parent::__construct($allocation);
        $this->threshold = $threshold;
    }

    /**
     * @param float $threshold
     */
    public function setThreshold(float $threshold): void
    {
        $this->threshold = $threshold;
    }

    public function needsRebalance(): bool
    {
        $values = $this->portfolio->getValues();
        $total = array_sum($values);

        foreach ($this->allocation as $ticker => $target) {
            //skip cash
            if ($ticker === Symbol::TICKER_USD) {
                continue;
            }

            if (abs($target - ($values[$ticker] ?? 0)  / $total) >= $this->threshold) {
                return true;
            }
        }

        return false;
    }
}
