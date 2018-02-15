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

    public function __construct(\DateTime $startDay, MarketData $marketData)
    {
        $this->marketData = $marketData;
        $this->timeline = new Timeline($startDay, $marketData->getTradingDays($startDay));
        $this->logger = new NullLogger();
    }

    public function deposit(float $amount)
    {
        $this->adjustPosition(self::CASH, $amount, 1);
        return $this;
    }

    public function withdraw(float $amount)
    {
        if($amount > $this->getPosition(self::CASH)) {
            throw new \LogicException('Cannot withdraw more than available cash');
        }

        $this->adjustPosition(self::CASH, -$amount, 1);
        return $this;
    }

    public function buy($symbol, $qty)
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $amount = $price * $qty;

        if($amount > $this->getPosition(self::CASH)) {
            throw new \LogicException('Cannot buy more than available cash');
        }

        $this->adjustPosition($symbol, $qty, $price);
        $this->adjustPosition(self::CASH, -$amount, 1);
        return $this;
    }

    public function sell($symbol, $qty)
    {
        if($qty > $pos = $this->getPosition($symbol)) {
            throw new \LogicException("Cannot sell($symbol $qty) more than available position ($pos)");
        }
        $price = $this->marketData->getClose($symbol, $this->timeline->today());

        $this->adjustPosition($symbol, -$qty, $price);
        $this->adjustPosition(self::CASH, $qty, 1);
        return $this;
    }

    public function getPosition($symbol)
    {
        return $this->position[$symbol] ?? 0;
    }

    protected function adjustPosition(string $symbol, float $qty, float $price): void
    {
        if(!array_key_exists($symbol, $this->position)) {
            $this->position[$symbol] = 0;
        }

        $this->logger->info(sprintf('Adjust: %+8d %-4s @ % 7.2f ', $qty, $symbol, $price));

        $this->position[$symbol] += $qty * $price;
    }

    public function forward()
    {
        $this->timeline->forward();
    }

    public function forwardTo(\DateTime $to)
    {
        $this->timeline->forwardTo(new \DateTime($to->format('Y-m-d')));
    }
}
