<?php


namespace TotalReturn\Api\Iex;

use TotalReturn\Api\Attribute;
use TotalReturn\Market\DividendInterface;
use TotalReturn\Market\HasSymbol;
use TotalReturn\Market\Symbol;

//https://iextrading.com/developer/docs/#dividends
class Dividend extends Attribute implements DividendInterface
{
    use HasSymbol;

    public function __construct(Symbol $symbol, array $json = [])
    {
        parent::__construct($json);
        $this->symbol = $symbol;
    }

    public function getExDate(): \DateTime
    {
        return new \DateTime($this->get('exDate'));
    }

    public function getPaymentDate(): \DateTime
    {
        return new \DateTime($this->get('paymentDate'));
    }

    public function getAmount(): float
    {
        return $this->get('amount');
    }
}
