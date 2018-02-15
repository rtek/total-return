<?php


namespace TotalReturn;


use Flintstone\Flintstone;
use TotalReturn\Av\Client as AvClient;

class MarketData
{
    /** @var AvClient */
    protected $av;

    protected $fs = [];

    public function __construct(AvClient $av)
    {
        $this->av = $av;
    }

    //@todo this is a stub
    public function getLastCloseDay()
    {
        return new \DateTime('2018-02-14');
    }

    //@todo this is a stub
    public function getTradingDays(\DateTime $after)
    {
        $today = new \DateTime('today');
        $day = new \DateTime($after->format('Y-m-d'));
        $days = [];

        while($day <= $today) {
            if($day->format('N') < 5) {
                $days[] = clone $day;
            }

            $day->modify('+1 day');
        }

        return $days;
    }

    public function getClose(string $symbol, \DateTime $day)
    {
        $daily = $this->getDaily($symbol, $day);
        return $daily['4. close'];
    }

    protected function getDaily(string $symbol, \DateTime $day)
    {
        $fs = $this->getFlintstone($db = 'daily-'. $symbol);

        if($fs->get($key = $day->format('Y-m-d')) === false) {
            //100 datapoints is not 100 trade days but close enough
            $size = $day > new \DateTime('now -100 days') ? 'compact' : 'full';
            $json = $this->av->getDaily($symbol);
            foreach($json['Time Series (Daily)'] as $day => $value) {
                $fs->set($day, $value);
            }
        }

        return $fs->get($key);
    }

    /**
     * @param string $db
     * @return Flintstone
     */
    protected function getFlintstone(string $db): Flintstone
    {
        if(!array_key_exists($db, $this->fs)) {
            $this->fs[$db] = new Flintstone($db, [
                'dir' => 'data/tmp/flintstone/'
            ]);
        }

        return $this->fs[$db];

    }
}
