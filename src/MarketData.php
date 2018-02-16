<?php


namespace TotalReturn;


use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use TotalReturn\Av\Client as AvClient;
use TotalReturn\Iex\Client as IexClient;

class MarketData
{
    use LoggerAwareTrait;

    const TRADEDAY_SYMBOL = 'SPY';
    const TRADEDAY_DAY = '2010-01-05';

    /** @var KeyValue */
    protected $kv;

    /** @var AvClient */
    protected $av;

    /** @var IexClient */
    protected $iex;

    protected $tradingDays;

    public function __construct(KeyValue $kv, AvClient $av, IexClient $iex)
    {
        $this->kv = $kv;
        $this->av = $av;
        $this->iex = $iex;
        $this->logger = new NullLogger();
    }

    public function getLastCloseDay(): \DateTime
    {
        $tradingDays = $this->getTradingDays();
        return $tradingDays[count($tradingDays) - 1];
    }

    public function getTradingDays(): array
    {
        if($this->tradingDays === null) {
            $this->getDaily(self::TRADEDAY_SYMBOL, new \DateTime(self::TRADEDAY_DAY));
            $this->tradingDays = array_map(function($id) {
                return new \DateTime($id);
            }, $this->kv->getIds('daily-'.self::TRADEDAY_SYMBOL));
        }

        return $this->tradingDays;
    }

    public function getTradingDaysAfter(\DateTime $after): array
    {
        $tradingDays = $this->getTradingDays();
              $i = 0;
        foreach($tradingDays as $i => $day) {
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

    public function getClose(string $symbol, \DateTime $day): float
    {
        if(!$this->isTradingDay($day)) {
            throw new \LogicException("{$day->format('Y-m-d')} is not a trading day");
        }

        $daily = $this->getDaily($symbol, $day);
        return (float)$daily['4. close'];
    }

    protected function getDaily(string $symbol, \DateTime $day): array
    {

        if(!$this->kv->has($ns = "daily-$symbol", $key = $day->format('Y-m-d'))) {
            //100 datapoints is not 100 trade days but close enough
            $size = $day > new \DateTime('now -100 days') ? 'compact' : 'full';
            $json = $this->av->getDaily($symbol, ['outputsize' => $size]);

            $replace = [];
            foreach ($json['Time Series (Daily)'] as $k => $v) {
                $replace[] = ['ns' => $symbol, 'id' => $k, 'value' => $v];
            }

            $this->kv->replaceMany($replace);
        }

        return $this->kv->get($ns, $key);
    }

    public function findDividend(string $symbol, \DateTime $exDate)
    {

    }

    protected function getDividends(string $symbol): array
    {
        $dividends = $this->iex->getDividends($symbol, '5y');

    }
}
