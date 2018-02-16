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

    protected $tradeDays;

    public function __construct(KeyValue $kv, AvClient $av, IexClient $iex)
    {
        $this->kv = $kv;
        $this->av = $av;
        $this->iex = $iex;
        $this->logger = new NullLogger();
    }

    //@todo this is a stub
    public function getLastCloseDay()
    {
        return new \DateTime('2018-02-14');
    }
    public function getTradingDays(\DateTime $after)
    {
        if($this->tradeDays === null) {
            $this->getDaily(self::TRADEDAY_SYMBOL, new \DateTime(self::TRADEDAY_DAY));
            $this->tradeDays = array_map(function($id) {
                return new \DateTime($id);
            }, $this->kv->getIds('daily-'.self::TRADEDAY_SYMBOL));
        }

        $i = 0;
        foreach($this->tradeDays as $i => $day) {
            if ($day >= $after) {
                break;
            }
        }

        return array_slice($this->tradeDays, $i);
    }

    public function getClose(string $symbol, \DateTime $day)
    {
        $daily = $this->getDaily($symbol, $day);
        return $daily['4. close'];
    }

    protected function getDaily(string $symbol, \DateTime $day)
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

}
