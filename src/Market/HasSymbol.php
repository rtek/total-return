<?php declare(strict_types=1);

namespace TotalReturn\Market;

trait HasSymbol
{
    /** @var Symbol */
    protected $symbol;

    public function getSymbol(): Symbol
    {
        return $this->symbol;
    }
}
