<?php


namespace TotalReturn\Market;

interface DividendInterface
{
    public function getSymbol(): Symbol;

    public function getExDate(): \DateTime;

    public function getPaymentDate(): \DateTime;

    public function getAmount(): float;

}
