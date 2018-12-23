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
    public const E_ADJUST = 'adjust';
    public const E_BEFORE_ADJUST = 'beforeadjust';
    public const E_DAY_END = 'dayend';
    public const E_DAY_START = 'daystart';

    /** @var Timeline */
    protected $timeline;

    /** @var MarketData */
    protected $marketData;

    /** @var array */
    protected $position = [];

    /** @var Dividend[] */
    protected $dividends = [];

    /** @var Symbol */
    protected $cash;

    /** @var float */
    protected $basis = 0;

    /** @var RebalancerInterface */
    protected $rebalancer;

    /** @var EventEmitter */
    protected $events;

    /** @var bool */
    protected $adjustedPositionToday = false;

    /**
     * @param \DateTime $startDay
     * @param MarketData $marketData
     */
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

    /**
     * @return EventEmitter
     */
    public function getEvents(): EventEmitter
    {
        return $this->events;
    }

    /**
     * @return Timeline
     */
    public function getTimeline(): Timeline
    {
        return $this->timeline;
    }

    /**
     * @return Symbol
     */
    public function getCashSymbol(): Symbol
    {
        return $this->cash;
    }

    /**
     * @return float
     */
    public function getCashValue(): float
    {
        return $this->position[$this->cash->getTicker()] ?? 0.0;
    }

    /**
     * @param RebalancerInterface $rebalancer
     * @return $this
     */
    public function setRebalancer(RebalancerInterface $rebalancer)
    {
        $rebalancer->setPortfolio($this);
        $this->rebalancer = $rebalancer;
        return $this;
    }

    /**
     * @return bool
     */
    public function getAdjustedPositionToday(): bool
    {
        return $this->adjustedPositionToday;
    }

    /**
     * @param float $amount
     */
    public function deposit(float $amount): void
    {
        $this->adjustPosition($this->cash, $this->roundAmount($amount), 1, 'Deposit');
    }

    /**
     * @param float $amount
     */
    public function withdraw(float $amount): void
    {
        $amount = $this->roundAmount($amount);
        $cash = $this->getPosition($this->cash);
        if ($this->roundAmount($amount - $cash) > 0) {
            throw new \LogicException("Cannot withdraw more than available cash ($amount vs $cash)");
        }

        $this->adjustPosition($this->cash, -$amount, 1, 'Withdraw');
    }

    /**
     * @param Symbol $symbol
     * @param float $qty
     * @return float
     */
    public function tradeQuantity(Symbol $symbol, float $qty): float
    {
        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $amount = $this->roundAmount($price * $qty);

        $this->tradeAmount($symbol, $amount);

        return $amount;
    }

    /**
     * @param Symbol $symbol
     * @param float $amount
     * @return float
     */
    public function tradeAmount(Symbol $symbol, float $amount): float
    {
        $amount = $this->roundAmount($amount);

        $price = $this->marketData->getClose($symbol, $this->timeline->today());
        $qty = $amount / $price;

        if ($amount >= 0) {
            $this->withdraw($amount);
            $this->adjustPosition($symbol, $qty, $price, sprintf('Buy %s', $symbol));
        } else {
            $this->adjustPosition($symbol, $qty, $price, sprintf('Sell %s', $symbol));
            $this->deposit(-$amount);
        }

        return $qty;
    }

    /**
     * @param Symbol $symbol
     * @return float
     */
    public function flatten(Symbol $symbol): float
    {
        return $this->tradeQuantity($symbol, -$this->getPosition($symbol));
    }

    /**
     * @param Symbol $symbol
     * @return float
     */
    public function getPosition(Symbol $symbol): float
    {
        return $this->position[$symbol->getTicker()] ?? 0;
    }

    /**
     * @return array
     */
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

    /**
     * Returns the portfolio value as a sum of all the held securities
     * @return float
     */
    public function getValue(): float
    {
        return array_sum($this->getValues());
    }

    /**
     * Same as getValue() but includes future owed dividends
     * @return float
     */
    public function getTotalValue(): float
    {
        $values = $this->getValues();

        foreach ($this->dividends as $dividend) {
            $values[$this->cash->getTicker()] += $this->roundAmount($dividend->getAmount() * $dividend->getPosition());
        }

        return array_sum($values);
    }

    /**
     * Returns the asset allocation of all held securities
     * @return array
     */
    public function getAllocation(): array
    {
        $values = $this->getValues();
        $total = array_sum($values);
        return array_map(function ($value) use ($total) {
            return $value / $total;
        }, $values);
    }

    /**
     * @param Symbol $symbol
     * @param float $qty
     * @param float $price
     * @param string $reason
     */
    protected function adjustPosition(Symbol $symbol, float $qty, float $price, string $reason): void
    {
        if (!array_key_exists($ticker = $symbol->getTicker(), $this->position)) {
            $this->position[$ticker] = 0;
        }

        $this->logger->debug(sprintf(
            '%s %+10.1f %-5s @ %7.2f %s',
            $this->timeline->formatToday(),
            $qty,
            $symbol,
            $price,
            $reason
        ));

        $this->position[$ticker] += $qty;

        $this->adjustedPositionToday = true;
    }

    /**
     * Progress the portfolio forward by one trading day
     *
     * * Adjust for splits
     * * Record dividend
     */
    public function forward(): void
    {
        $today = $this->timeline->today();
        $this->adjustedPositionToday = false;

        $this->getEvents()->emit(self::E_DAY_START, [$this]);

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

        $this->getEvents()->emit(self::E_DAY_END, [$this]);

        $this->timeline->forward();
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
