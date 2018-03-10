<?php declare(strict_types=1);

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

    public function needsRebalance(): bool
    {
        foreach ($this->allocation as $ticker => $alloc) {
            if ($this->isOutsideRange($ticker, $this->rebalance)) {
                return true;
            }
        }

        return false;
    }

    public function calculateTrades(): array
    {
        $values = $this->portfolio->getValues();
        $totalValue = array_sum($values);
        $cashTicker = $this->portfolio->getCashSymbol()->getTicker();
        $cash = $values[$cashTicker];
        unset($values[$cashTicker]);

        $trades = $this->flattenOthers($values);

        $ibErrors = $obErrors = [];

        foreach ($this->allocation as $ticker => $target) {
            $actual = ($values[$ticker] ?? 0) / $totalValue;

            if ($dir = $this->isOutsideRange($ticker, $this->rebalance)) {
                //outside range are brought into the tolerance at a minimum
                $error =  $actual - $target;
                $trades[$ticker] = -$error * $totalValue * (1 - $this->tolerance);
                $obErrors[$ticker] = $error * $totalValue * $this->tolerance;
            } else {
                if($dir = $this->isOutsideRange($ticker, $this->tolerance)) {
                    //inside ranges can be brought to the other side of the tolerance
                    $error = $actual - $target * (1 + $this->tolerance) * $dir;
                    $ibErrors[$ticker] = $error * $totalValue;
                } else {
                    //do nothing if they're in the tolerance band
                }
            }
        }

        $cashAvail = round($cash - array_sum($trades), 2);

        //if there is > 1 OB trades bring them in to balance
        if(count($obErrors) > 1) {
            $totalError = array_sum($obErrors);
            foreach ($obErrors as $ticker => $error) {
                $trades[$ticker] += $delta = $cashAvail * $error / $totalError;
                $obErrors[$ticker] -= $delta;
            }
        } else { //otherwise we need to go IB
            if ($cashAvail > 0) {
                //error is negative, need to buy
                asort($ibErrors);

                while ($cashAvail = round($cash - array_sum($trades), 2)) {
                    foreach ($ibErrors as $ticker => $error) {
                        $trades[$ticker] = min(-$error, $cashAvail);
                        unset($ibErrors[$ticker]);
                        break;
                    }
                }
            } else {
                //error is positive, need to sell
                arsort($ibErrors);

                while ($cashNeeded = round(array_sum($trades) - $cash, 2)) {
                    foreach ($ibErrors as $ticker => $error) {
                        $trades[$ticker] = max(-$error, -$cashNeeded);
                        unset($ibErrors[$ticker]);
                        break;
                    }
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
