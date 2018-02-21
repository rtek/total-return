<?php


namespace TotalReturn\Portfolio\Rebalancer;

//http://resource.fpanet.org/resource/09BBF2F9-D5B3-9B76-B02E27EB8731C337/daryanani.pdf
use TotalReturn\Portfolio\Portfolio;

class Daryanani extends AbstractRebalancer
{
    /** @var float */
    protected $rebalance;
    /** @var float */
    protected $tolerance;
    /** @var int */
    protected $interval;

    public function __construct(array $allocation, int $interval, float $rebalance, float $tolerance)
    {
        parent::__construct($allocation);
        //@todo interval limiting
        $this->interval = $interval;
        $this->rebalance = $rebalance;
        $this->tolerance = $tolerance;
    }

    public function needsRebalance(Portfolio $portfolio): bool
    {
        $values = $portfolio->getValues();
        $total = array_sum($values);

        foreach($this->allocation as $ticker => $alloc) {
            if($this->isOutsideRange($ticker, ($values[$ticker] ?? 0) / $total)) {
                return true;
            }
        }

        return false;
    }

    public function calculateTrades(Portfolio $portfolio): array
    {
        $values = $portfolio->getValues();
        $totalValue = array_sum($values);
        $cashTicker = $portfolio->getCashSymbol()->getTicker();
        $cash = $values[$cashTicker];
        unset($values[$cashTicker]);

        $trades = $this->flattenOthers($values);

        $deltas = [];
        foreach($this->allocation as $ticker => $targetAlloc) {
            $value = $values[$ticker] ?? 0;
            $delta = $targetAlloc * $totalValue - $value;
            if($this->isOutsideRange($ticker, $value / $totalValue)) {
                $trades[$ticker] = $delta;
            } else {
                $deltas[$ticker] = $delta;
            }
        }

        asort($deltas);

        while (0.0 < $cashNeeded = round(array_sum($trades) - $cash, 2)) {

            foreach($deltas as $ticker => $delta) {
                $trades[$ticker] = max($delta, -$cashNeeded);
                unset($deltas[$ticker]);
                break;
            }
        }


        return $trades;
    }

    protected function isOutsideRange(string $ticker, float $actualAlloc): int
    {
        $targetAlloc = $this->allocation[$ticker] ?? 0;
        return abs(1 - $actualAlloc / $targetAlloc)  >= $this->rebalance ? $targetAlloc <=> $actualAlloc : 0;
    }

    protected function flattenOthers(array $values): array
    {
        $trades = [];
        foreach($values as $ticker => $value) {
            if(!array_key_exists($ticker, $this->allocation)) {
                $trades[$ticker] = -$value;
            }
        }
        return $trades;
    }
}
