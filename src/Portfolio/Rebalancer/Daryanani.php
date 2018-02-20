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
        $total = array_sum($values);
        unset($values[$portfolio->getCashSymbol()->getTicker()]);

        $trades = $this->flattenOthers($values);
        foreach($this->allocation as $ticker => $targetAlloc) {
            $value = $values[$ticker] ?? 0;
            if($sign = $this->isOutsideRange($ticker, $value / $total)) {
                $trades[$ticker] = $sign * $targetAlloc * (1 + $this->tolerance) * $total;
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
