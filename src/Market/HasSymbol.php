<?php


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
