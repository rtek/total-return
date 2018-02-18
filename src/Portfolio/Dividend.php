<?php


namespace TotalReturn\Portfolio;

use TotalReturn\Market\DividendInterface;
use TotalReturn\Market\Symbol;

class Dividend implements DividendInterface
{
    /** @var DividendInterface */
    protected $dividend;

    /** @var float */
    protected $position;

    public function __construct(DividendInterface $dividend, float $position)
    {
        $this->dividend = $dividend;
        $this->position = $position;
    }

    public function getSymbol(): Symbol
    {
        return $this->dividend->getSymbol();
    }

    public function getExDate(): \DateTime
    {
        return $this->dividend->getExDate();
    }

    public function getPaymentDate(): \DateTime
    {
        return $this->dividend->getPaymentDate();
    }

    public function getAmount(): float
    {
        return $this->dividend->getAmount();
    }

    public function getPosition(): float
    {
        return $this->position;
    }
}
