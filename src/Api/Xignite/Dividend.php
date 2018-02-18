<?php


namespace TotalReturn\Api\Xignite;

use TotalReturn\Api\Attribute;
use TotalReturn\Market\DividendInterface;
use TotalReturn\Market\HasSymbol;
use TotalReturn\Market\Symbol;


class Dividend extends Attribute implements DividendInterface
{
    const DIV_DAILY_ACCRUAL = 'DailyAccrualFund';
    const DIV_LT_CG = 'CapitalGainLongTerm';
    const DIV_ST_CG = 'CapitalGainShortTerm';
    const DIV_NONE = 'None'; //wtf?

    use HasSymbol;

    public function __construct(Symbol $symbol, array $json = [])
    {
        parent::__construct($json);
        $this->symbol = $symbol;
    }

    public function getExDate(): \DateTime
    {
        switch($type = $this->getType()) {
            case self::DIV_DAILY_ACCRUAL:
                return new \DateTime($this->get('PayDate'));
            case self::DIV_LT_CG:
            case self::DIV_ST_CG:
            case self::DIV_NONE:
                return new \DateTime($this->get('ExDate'));
        }

        throw new \LogicException("Unexpected type: $type");
    }

    public function getPaymentDate(): \DateTime
    {
        return new \DateTime($this->get('PayDate'));
    }

    public function getAmount(): float
    {
        return $this->get('DividendAmount');
    }

    public function getType(): string
    {
        return $this->get('Type');
    }
}
