<?php declare(strict_types=1);

namespace TotalReturn\Market;

class Symbol
{
    const TICKER_USD = '$USD';

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

    protected static $symbols = [];

    //@todo validation / etc
    public static function lookup(string $ticker): self
    {
        if (!array_key_exists($ticker, self::$symbols)) {
            self::$symbols[$ticker] = new self($ticker);
        }
        return self::$symbols[$ticker];
    }

    public static function USD(): self
    {
        return self::lookup(self::TICKER_USD);
    }
}
