<?php declare(strict_types=1);

namespace TotalReturn;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TotalReturn\Api\Av\Client as AvClient;
use TotalReturn\Api\Av\Daily;
use TotalReturn\Api\Iex\Client as IexClient;
use TotalReturn\Api\Xignite\Client as XigniteClient;
use TotalReturn\Api\Xignite\Split;
use TotalReturn\KeyValue\Kv;
use TotalReturn\KeyValue\Ns;
use TotalReturn\KeyValue\Store;
use TotalReturn\Market\DividendInterface;
use TotalReturn\Market\Symbol;

class MarketData
{
    use LoggerAwareTrait;

    public const TRADEDAY_TICKER = 'SPY';
    public const TRADEDAY_DAY = '2010-01-05';

    /** @var Store */
    protected $kv;

    /** @var AvClient */
    protected $av;

    /** @var IexClient */
    protected $iex;

    /** @var XigniteClient */
    protected $xig;

    protected $tradingDays;

    public function __construct(Store $kv, AvClient $av, IexClient $iex, XigniteClient $xig)
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
            $symbol = Symbol::lookup(self::TRADEDAY_TICKER);
            $this->getDaily($symbol, new \DateTime(self::TRADEDAY_DAY));
            $this->tradingDays = array_map(function ($id) {
                return new \DateTime($id);
            }, $this->kv->getIds(Ns::daily($symbol)));
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
        return $daily->getClose();
    }

    protected function getDaily(Symbol $symbol, \DateTime $day): Daily
    {
        if (!$this->kv->has($ns = Ns::daily($symbol), $id = Ns::id($day))) {
            //100 datapoints is not 100 trade days but close enough
            $size = $day > new \DateTime('now -100 days') ? 'compact' : 'full';

            $this->logger->debug("Fetching daily $size for $symbol");
            $json = $this->av->getDaily($symbol->getTicker(), ['outputsize' => $size]);

            $replace = [];
            foreach ($json['Time Series (Daily)'] as $k => $v) {
                $replace[] = new Kv($ns, $k, new Daily($v));
            }

            $this->kv->replaceMany($replace);
        }

        $daily = $this->kv->get($ns, $id);

        if (!$daily instanceof Daily) {
            throw new \LogicException("$ns $id did not return Daily");
        }

        return $daily;
    }

    public function findDividend(Symbol $symbol, \DateTime $exDate): ?DividendInterface
    {
        $this->updateDividends($symbol);

        return $this->kv->get(Ns::dividend($symbol), Ns::id($exDate)) ?? null;
    }

    protected function updateDividends(Symbol $symbol): void
    {
        $lastUpdated = new \DateTime($this->kv->get(Ns::dividendUpdate(), Ns::id($symbol)) ?? 'today -1 day');

        if ($lastUpdated < new \DateTime('today')) {
            $ticker = $symbol->getTicker();
            $this->logger->debug("Fetching dividends for $ticker");

            $dividends = $this->xig->getDividends($ticker);

            $replace = [];
            foreach ($dividends as $d) {
                $replace[] = new Kv(Ns::dividend($symbol), Ns::id($d->getExDate()), $d);
            }

            $this->kv->replaceMany($replace);
            $this->kv->replace(new Kv(Ns::dividendUpdate(), Ns::id($symbol), date('Y-m-d')));
        }
    }

    public function findSplit(Symbol $symbol, \DateTime $exDate): ?Split
    {
        $this->updateSplits($symbol);

        return $this->kv->get(Ns::split($symbol), Ns::id($exDate)) ?? null;
    }

    protected function updateSplits(Symbol $symbol): void
    {
        $lastUpdated = new \DateTime($this->kv->get(Ns::splitUpdate(), Ns::id($symbol)) ?? 'today -1 day');

        if ($lastUpdated < new \DateTime('today')) {
            $ticker = $symbol->getTicker();
            $this->logger->debug("Fetching splits for $ticker");

            $splits = $this->xig->getSplits($ticker);

            $replace = [];
            foreach ($splits as $d) {
                $replace[] = new Kv(Ns::split($symbol), Ns::id($d->getExDate()), $d);
            }

            $this->kv->replaceMany($replace);
            $this->kv->replace(new Kv(Ns::splitUpdate(), Ns::id($symbol), date('Y-m-d')));
        }
    }
}
