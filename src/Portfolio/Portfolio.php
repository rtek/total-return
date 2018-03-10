<?php declare(strict_types=1);

namespace TotalReturn\Portfolio;

use Evenement\EventEmitter;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TotalReturn\Market\Symbol;
use TotalReturn\MarketData;
use TotalReturn\Portfolio\Rebalancer\Manual;
use TotalReturn\Portfolio\Rebalancer\RebalancerInterface;

class Portfolio
{
    use LoggerAwareTrait;

    public const E_REBALANCE = 'rebalance';
    public const E_BEFORE_REBALANCE = 'beforerebalance';
    public const E_FORWARD = 'forward';

    /** @var Timeline */
    protected $timeline;

    /** @var MarketData */
    protected $marketData;

    protected $position = [];

    /** @var Dividend[] */
    protected $dividends = [];

    /** @var Symbol */
    protected $cash;

    /** @var RebalancerInterface */
    protected $rebalancer;

    /** @var EventEmitter */
    protected $events;

    public function __construct(\DateTime $startDay, MarketData $marketData)
    {
        $this->marketData = $marketData;
        $this->timeline = new Timeline($startDay, $marketData->getTradingDaysAfter($startDay));
        $this->logger = new NullLogger();
        $this->cash = Symbol::USD();
        $this->rebalancer = new Manual([
            $this->cash->getTicker() => 1,
        ]);

        $this->events = new EventEmitter();
    }

    public function getEvents(): EventEmitter
    {
        return $this->events;
    }

    public function getTimeline(): Timeline
    {
        return $this->timeline;
    }

    public function getCashSymbol(): Symbol
    {
        return $this->cash;
    }

    public function setRebalancer(RebalancerInterface $rebalancer)
    {
        $rebalancer->setPortfolio($this);
        $this->rebalancer = $rebalancer;
        return $this;
    }

    public function deposit(float $amount): void
    {
        $this->adjustPosition($this->cash, $this->roundAmount($amount), 1, 'Deposit');
    }

    public function withdraw(float $amount): void
    {
        $amount = $this->roundAmount($amount);
        $cash = $this->getPosition($this->cash);
        if ($this->roundAmount($amount - $cash) > 0) {
            throw new \LogicException("Cannot withdraw more than available cash ($amount vs $cash)");
        }

        $this->adjustPosition($this->cash, -$amount, 1, 'Withdraw');
    }

    public function tradeQuantity(Symbol $symbol, float $qty): float
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $amount = $this->roundAmount($price * $qty);

        $this->tradeAmount($symbol, $amount);

        return $amount;
    }

    public function tradeAmount(Symbol $symbol, float $amount): float
    {
        $amount = $this->roundAmount($amount);

        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $qty = $amount / $price;

        if($amount >= 0) {
            $this->withdraw($amount);
            $this->adjustPosition($symbol, $qty, $price, "Buy $symbol");
        } else {
            $this->adjustPosition($symbol, $qty, $price, "Sell $symbol");
            $this->deposit(-$amount);
        }

        return $qty;
    }

    public function flatten(Symbol $symbol): float
    {
        return $this->tradeQuantity($symbol, -$this->getPosition($symbol));
    }

    public function getPosition(Symbol $symbol): float
    {
        return $this->position[$symbol->getTicker()] ?? 0;
    }

    public function getValues(): array
    {
        $today = $this->timeline->today();
        $values = [];
        foreach ($this->position as $ticker => $qty) {
            $price = $ticker === $this->cash->getTicker() ? 1 : $this->marketData->getClose(Symbol::lookup($ticker), $today);
            $values[$ticker] = $this->roundAmount($qty * $price);
        }

        return $values;
    }

    public function getValue(): float
    {
        return array_sum($this->getValues());
    }

    public function getTotalValue(): float
    {
        $values = $this->getValues();

        foreach ($this->dividends as $dividend) {
            $values[$this->cash->getTicker()] += $this->roundAmount($dividend->getAmount() * $dividend->getPosition());
        }

        return array_sum($values);
    }

    public function getAllocation(): array
    {
        $values = $this->getValues();
        $total = array_sum($values);
        return array_map(function ($value) use ($total) {
            return $value / $total;
        }, $values);
    }

    protected function adjustPosition(Symbol $symbol, float $qty, float $price, string $reason): void
    {
        if (!array_key_exists($ticker = $symbol->getTicker(), $this->position)) {
            $this->position[$ticker] = 0;
        }

        $this->logger->info(sprintf('%s: %+10.2f %-5s @ %7.2f %s', $this->timeline->today()->format('Y-m-d'), $qty, $symbol, $price, $reason));

        $this->position[$ticker] += $qty;
    }

    public function forward(): void
    {
        $today = $this->timeline->today();

        foreach ($this->position as $ticker => $pos) {
            $symbol = Symbol::lookup($ticker);

            if ($symbol->hasSplits() && $split = $this->marketData->findSplit($symbol, $today)) {
                $ratio = $split->getRatio();
                $pos = $this->getPosition($symbol);
                $this->adjustPosition($symbol, $pos * $ratio - $pos, 0.0, sprintf('Split %.2f x %.2f = %.2f', $pos, $ratio, $pos * $ratio));
            }

            if ($symbol->hasDividends() && $dividend = $this->marketData->findDividend($symbol, $today)) {
                $this->dividends[] = new Dividend($dividend, $this->getPosition($symbol));
            }
        }

        foreach ($this->dividends as $i => $dividend) {
            if ($dividend->getPaymentDate() == $today) {
                $price = $this->marketData->getClose($symbol = $dividend->getSymbol(), $today);
                $amt = $dividend->getAmount();
                $pos = $dividend->getPosition();
                $qty = $this->roundAmount($pos * $amt) / $price;
                $this->adjustPosition($symbol, $qty, $price, sprintf('Dividend re-invest @ %.2f x %.4f = $%.2f', $pos, $amt, $qty * $price));

                unset($this->dividends[$i]);
            }
        }

        if ($this->rebalancer->needsRebalance($this)) {
            $this->events->emit(self::E_BEFORE_REBALANCE, [$this]);
            $this->rebalance();
            $this->events->emit(self::E_REBALANCE, [$this]);
        }

        $this->timeline->forward();

        $this->getEvents()->emit(self::E_FORWARD, [$this]);
    }

    public function forwardTo(\DateTime $to): void
    {
        while (!$this->timeline->isEnd() && $this->timeline->next() <= $to) {
            $this->forward();
        }
    }

    public function rebalance(): void
    {
        $this->rebalancer->rebalance($this);
    }

    protected function roundAmount(float $amount): float
    {
        return ($amount >= 0 ? 'floor' : 'ceil')($amount * 100) / 100;
    }
}
