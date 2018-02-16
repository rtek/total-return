<?php

namespace TotalReturn\Portfolio;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TotalReturn\MarketData;

class Portfolio
{
    use LoggerAwareTrait;

    const CASH = '$';

    /** @var Timeline */
    protected $timeline;

    /** @var MarketData */
    protected $marketData;

    protected $position = [];

    protected $dividends = [];

    public function __construct(\DateTime $startDay, MarketData $marketData)
    {
        $this->marketData = $marketData;
        $this->timeline = new Timeline($startDay, $marketData->getTradingDays($startDay));
        $this->logger = new NullLogger();
    }

    public function deposit(float $amount)
    {
        $this->adjustPosition(self::CASH, $amount);
        return $this;
    }

    public function withdraw(float $amount)
    {
        if($amount > $this->getPosition(self::CASH)) {
            throw new \LogicException('Cannot withdraw more than available cash');
        }

        $this->adjustPosition(self::CASH, -$amount);
        return $this;
    }

    public function buy($symbol, $qty)
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $amount = $price * $qty;

        if($amount > $this->getPosition(self::CASH)) {
            throw new \LogicException('Cannot buy more than available cash');
        }

        $this->adjustPosition($symbol, $qty);
        $this->adjustPosition(self::CASH, -$amount);
        return $this;
    }

    public function sell($symbol, $qty)
    {
        if($qty > $pos = $this->getPosition($symbol)) {
            throw new \LogicException("Cannot sell($symbol $qty) more than available position ($pos)");
        }
        $price = $this->marketData->getClose($symbol, $this->timeline->today());

        $this->adjustPosition($symbol, -$qty);
        $this->adjustPosition(self::CASH, $qty * $price);
        return $this;
    }

    public function getPosition($symbol)
    {
        return $this->position[$symbol] ?? 0;
    }

    protected function adjustPosition(string $symbol, float $qty): void
    {
        if(!array_key_exists($symbol, $this->position)) {
            $this->position[$symbol] = 0;
        }

        $this->logger->info(sprintf('Adjust: %+8d %-4s', $qty, $symbol));

        $this->position[$symbol] += $qty;
    }

    public function forward()
    {
        $today = $this->timeline->today();

        foreach($this->position as $symbol => $pos) {
            if ($dividend = $this->marketData->findDividend($symbol, $today)) {
                $this->dividends[] = array_merge($dividend, [
                    'symbol' => $symbol
                ]);
            }
        }

        foreach($this->dividends as $i => $dividend) {
            if ($dividend['paymentDate'] == $today) {

                $price = $this->marketData->getClose($dividend['symbol'], $today);
                $this->adjustPosition($dividend['symbol'], $dividend['amount'] / $price);

                unset($this->dividends[$i]);
            }
        }

        $this->timeline->forward();
    }

    public function forwardTo(\DateTime $to)
    {
        while ($this->timeline->today() < $to) {
            $this->forward();
        }
    }
}
