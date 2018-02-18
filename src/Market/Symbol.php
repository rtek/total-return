<?php


namespace TotalReturn\Market;


class Symbol
{
    /** @var string */
    protected $ticker;

    public function __construct(string $ticker)
    {
        $this->ticker = $ticker;
    }

    public function getTicker(): string
    {
        return $this->ticker;
    }

    public function hasDividends(): bool
    {
        return stripos($this->ticker, '$') !== 0;
    }

    public function isMutualFund(): bool
    {
        return strtoupper(substr($this->ticker, -1)) === 'X';
    }

    public function __toString()
    {
        return $this->getTicker();
    }


    static protected $symbols = [];

    //@todo validation / etc
    static public function lookup(string $ticker): Symbol
    {
        if (!array_key_exists($ticker, self::$symbols)) {
           self::$symbols[$ticker] = new Symbol($ticker);
        }
        return self::$symbols[$ticker];
    }
}
