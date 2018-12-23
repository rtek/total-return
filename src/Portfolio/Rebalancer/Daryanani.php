<?php declare(strict_types=1);

namespace TotalReturn\Portfolio\Rebalancer;

/**
 * An interpretation of data/docs/daryanani.pdf
 */
class Daryanani extends AbstractRebalancer
{
    /** @var float */
    protected $rebalance;
    /** @var float */
    protected $tolerance;
    /** @var int */
    protected $interval;

    public function __construct(array $allocation, int $interval, float $rebalance, float $tolerance = null)
    {
        parent::__construct($allocation);
        //@todo interval limiting
        $this->interval = $interval;
        $this->rebalance = $rebalance;
        $this->tolerance = $tolerance ?? $rebalance / 2;
    }

    public function needsRebalance(): bool
    {
        foreach ($this->allocation as $ticker => $alloc) {
            if ($this->isOutsideRange($ticker, $this->rebalance)) {
                return true;
            }
        }

        return $this->portfolio->getCashValue() / $this->portfolio->getValue() > (1 - array_sum($this->allocation)) * (1 + $this->tolerance);
    }

    public function calculateTrades(): array
    {
        $values = $this->portfolio->getValues();
        $totalValue = array_sum($values);
        $cashTicker = $this->portfolio->getCashSymbol()->getTicker();
        $cash = $values[$cashTicker];
        unset($values[$cashTicker]);

        $trades = $this->flattenOthers($values);

        //ib = in rebalance band
        $ibErrors = $allErrors = [];

        foreach ($this->allocation as $ticker => $target) {
            $actual = ($values[$ticker] ?? 0) / $totalValue;
            $error =  $actual - $target;
            $allErrors[$ticker] = $error * $totalValue;
            if ($dir = $this->isOutsideRange($ticker, $this->rebalance)) {
                //outside rebalance are brought to the target
                $trades[$ticker] = -$error * $totalValue;
            } elseif ($dir = $this->isOutsideRange($ticker, $this->tolerance)) {
                //inside rebalance can be brought the other side of the tolerance
                $ibErrors[$ticker] = $error * (1 + $this->tolerance) * $dir;
            } else {
                //inside tolerance can be brought to the target
                $ibErrors[$ticker] = $error;
            }
        }

        arsort($ibErrors);

        //raise cash from the largest in-band errors
        while (0 < $cashNeeded = round(array_sum($trades) - $cash, 2)) {
            if (count($ibErrors) === 0) {
                throw new \LogicException('Cannot raise cash');
            }

            $ticker = key($ibErrors);
            $error = array_shift($ibErrors);

            $trades[$ticker] = max(-$cashNeeded, -$error * $totalValue);
        }

        $projectedCash = round($cash - array_sum($trades), 2);

        //only distribute free cash to existing buys, then by negative error amount
        if (0 < $freeCash = $projectedCash - $totalValue *  (1 - array_sum($this->allocation))) {
            $errors = array_filter($allErrors, function ($e) {
                return $e < 0;
            });

            if (count($trades) > 0) {
                $errors = array_intersect_key($errors, $trades);
            }

            asort($errors);

            foreach ($errors as $ticker => $error) {
                $trades[$ticker] = ($trades[$ticker] ?? 0) + $trade = min($freeCash, -$error);
                $freeCash -= $trade;
                if ($freeCash <= 0) {
                    break;
                }
            }
        }

        return $trades;
    }

    protected function isOutsideRange(string $ticker, float $range): int
    {
        $values = $this->portfolio->getValues();
        $actual = ($values[$ticker] ?? 0) / array_sum($values);
        $target = $this->allocation[$ticker] ?? 0;
        return abs(1 - $actual / $target)  >= $range ? $target <=> $actual : 0;
    }

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
