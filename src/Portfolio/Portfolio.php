<?php declare(strict_types=1);

namespace TotalReturn\Portfolio;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TotalReturn\Market\Symbol;
use TotalReturn\MarketData;

class Portfolio
{
    use LoggerAwareTrait;

    /** @var Timeline */
    protected $timeline;

    /** @var MarketData */
    protected $marketData;

    protected $position = [];

    /** @var Dividend[] */
    protected $dividends = [];

    /** @var Symbol */
    protected $cash;

    public function __construct(\DateTime $startDay, MarketData $marketData)
    {
        $this->marketData = $marketData;
        $this->timeline = new Timeline($startDay, $marketData->getTradingDaysAfter($startDay));
        $this->logger = new NullLogger();
        $this->cash = Symbol::USD();
    }

    public function deposit(float $amount)
    {
        $this->adjustPosition($this->cash, $amount, 1, 'Deposit');
        return $this;
    }

    public function withdraw(float $amount)
    {
        if ($amount > $this->getPosition($this->cash)) {
            throw new \LogicException('Cannot withdraw more than available cash');
        }

        $this->adjustPosition($this->cash, -$amount, 1, 'Withdraw');
        return $this;
    }

    public function buyQuantity(Symbol $symbol, float $qty): float
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $amount = round($price * $qty, 2);

        $this->buyAmount($symbol, $amount);
        return $amount;
    }

    public function buyAmount(Symbol $symbol, float $amount): float
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $qty = $amount / $price;

        $this->withdraw($amount);
        $this->adjustPosition($symbol, $qty, $price, "Buy $symbol");

        return $qty;
    }

    public function sellQuantity(Symbol $symbol, float $qty): float
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $amount = round($price * $qty, 2);

        $this->sellAmount($symbol, $amount);
        return $amount;
    }

    public function sellAmount(Symbol $symbol, float $amount): float
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $qty = $amount / $price;

        $this->adjustPosition($symbol, -$qty, $price, "Sell $symbol");
        $this->deposit($amount);
        return $qty;
    }

    public function flatten(Symbol $symbol): float
    {
        $pos = $this->getPosition($symbol);
        return $pos > 0 ? $this->sellQuantity($symbol, $pos) : $this->buyQuantity($symbol, $pos);
    }

    public function getPosition(Symbol $symbol): float
    {
        return $this->position[$symbol->getTicker()] ?? 0;
    }

    public function getValues(): array
    {
        $today = $this->timeline->today();
        $values = [];
        foreach($this->position as $ticker => $qty) {
            $price = $ticker === $this->cash->getTicker() ? 1 : $this->marketData->getClose(Symbol::lookup($ticker), $today);
            $values[$ticker] = round($qty * $price,2);
        }

        foreach($this->dividends as $dividend) {
            $values[$this->cash->getTicker()] += round($dividend->getAmount() * $dividend->getPosition(),2);
        }

        return $values;
    }

    public function getValue(): float
    {
        return array_sum($this->getValues());
    }

    protected function adjustPosition(Symbol $symbol, float $qty, float $price, string $reason): void
    {
        if (!array_key_exists($ticker = $symbol->getTicker(), $this->position)) {
            $this->position[$ticker] = 0;
        }

        $this->logger->info(sprintf('Adjust: %+10.2f %-5s @ %7.2f %s %s', $qty, $symbol, $price, $this->timeline->today()->format('Y-m-d'), $reason));

        $this->position[$ticker] += $qty;
    }

    public function forward(): void
    {
        $today = $this->timeline->today();

        foreach ($this->position as $ticker => $pos) {
            $symbol = Symbol::lookup($ticker);

            if ($symbol->hasDividends() && $dividend = $this->marketData->findDividend($symbol, $today)) {
                $this->dividends[] = new Dividend($dividend, $this->getPosition($symbol));
            }
        }

        foreach ($this->dividends as $i => $dividend) {
            if ($dividend->getPaymentDate() == $today) {
                $price = $this->marketData->getClose($symbol = $dividend->getSymbol(), $today);
                $amt = $dividend->getAmount();
                $this->adjustPosition($symbol, $amt * $dividend->getPosition() / $price, $price, "Dividend re-invest @ $amt");

                unset($this->dividends[$i]);
            }
        }

        $this->timeline->forward();
    }

    public function forwardTo(\DateTime $to): void
    {
        while (!$this->timeline->isEnd() && $this->timeline->today() < $to) {
            $this->forward();
        }
    }
}
