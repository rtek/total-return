<?php declare(strict_types=1);

namespace TotalReturn;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TotalReturn\Api\Av\Client as AvClient;
use TotalReturn\Api\Iex\Client as IexClient;
use TotalReturn\Api\Xignite\Client as XigniteClient;
use TotalReturn\Market\Symbol;

class MarketData
{
    use LoggerAwareTrait;

    public const TRADEDAY_TICKER = 'SPY';
    public const TRADEDAY_DAY = '2010-01-05';

    /** @var KeyValue */
    protected $kv;

    /** @var AvClient */
    protected $av;

    /** @var IexClient */
    protected $iex;

    /** @var XigniteClient */
    protected $xig;

    protected $tradingDays;

    public function __construct(KeyValue $kv, AvClient $av, IexClient $iex, XigniteClient $xig)
    {
        $this->kv = $kv;
        $this->av = $av;
        $this->iex = $iex;
        $this->xig = $xig;
        $this->logger = new NullLogger();
    }

    public function getLastCloseDay(): \DateTime
    {
        $tradingDays = $this->getTradingDays();
        return $tradingDays[count($tradingDays) - 1];
    }

    public function getTradingDays(): array
    {
        if ($this->tradingDays === null) {
            $this->getDaily(new Symbol(self::TRADEDAY_TICKER), new \DateTime(self::TRADEDAY_DAY));
            $this->tradingDays = array_map(function ($id) {
                return new \DateTime($id);
            }, $this->kv->getIds('daily-'.self::TRADEDAY_TICKER));
        }

        return $this->tradingDays;
    }

    public function getTradingDaysAfter(\DateTime $after): array
    {
        $tradingDays = $this->getTradingDays();
        $i = 0;
        foreach ($tradingDays as $i => $day) {
            if ($day >= $after) {
                break;
            }
        }

        return array_slice($tradingDays, $i);
    }

    public function isTradingDay(\DateTime $day): bool
    {
        $tradingDays = $this->getTradingDays();

        return array_search($day, $this->tradingDays) !== false;
    }

    public function getClose(Symbol $symbol, \DateTime $day): float
    {
        if (!$this->isTradingDay($day)) {
            throw new \LogicException("{$day->format('Y-m-d')} is not a trading day");
        }

        $daily = $this->getDaily($symbol, $day);
        return (float)$daily['4. close'];
    }

    protected function getDaily(Symbol $symbol, \DateTime $day): array
    {
        $ticker = $symbol->getTicker();

        if (!$this->kv->has($ns = "daily-$ticker", $id = $day->format('Y-m-d'))) {
            //100 datapoints is not 100 trade days but close enough
            $size = $day > new \DateTime('now -100 days') ? 'compact' : 'full';

            $this->logger->debug("Fetching daily $size for $symbol");
            $json = $this->av->getDaily($ticker, ['outputsize' => $size]);

            $replace = [];
            foreach ($json['Time Series (Daily)'] as $k => $v) {
                $replace[] = ['ns' => $ns, 'id' => $k, 'value' => $v];
            }

            $this->kv->replaceMany($replace);
        }

        $daily = $this->kv->get($ns, $id);

        if (!is_array($daily)) {
            throw new \LogicException("$ns $id did not return an array");
        }

        return $daily;
    }

    public function findDividend(Symbol $symbol, \DateTime $exDate)
    {
        $this->updateDividends($symbol);

        return $this->kv->get("dividend-$symbol", $exDate->format('Y-m-d')) ?? null;
    }

    protected function updateDividends(Symbol $symbol): void
    {
        $lastUpdated = new \DateTime($this->kv->get('dividend-update', $ticker = $symbol->getTicker()) ?? 'today -1 day');

        if ($lastUpdated < new \DateTime('today')) {
            $this->logger->debug("Fetching dividends for $ticker");

            $dividends = $this->xig->getDividends($ticker);

            $replace = [];
            foreach ($dividends as $d) {
                $replace[] = ['ns' => "dividend-$ticker", 'id' => $d->getExDate()->format('Y-m-d'), 'value' => $d];
            }

            $this->kv->replaceMany($replace);
            $this->kv->replace('dividend-update', $ticker, date('Y-m-d'));
        }
    }
}
