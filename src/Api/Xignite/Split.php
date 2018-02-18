<?php declare(strict_types=1);

namespace TotalReturn\Api\Xignite;

use TotalReturn\Api\Attribute;
use TotalReturn\Market\HasSymbol;
use TotalReturn\Market\Symbol;

class Split extends Attribute
{
    use HasSymbol;

    public function __construct(Symbol $symbol, array $json = [])
    {
        parent::__construct($json);
        $this->symbol = $symbol;
    }

    public function getExDate(): \DateTime
    {
        return new \DateTime($this->get('ExDate'));
    }

    public function getRatio(): float
    {
        return $this->get('SplitRatio');
    }
}
