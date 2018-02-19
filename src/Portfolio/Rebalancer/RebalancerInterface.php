<?php

namespace TotalReturn\Portfolio\Rebalancer;

use TotalReturn\Portfolio\Portfolio;

interface RebalancerInterface
{
    public function needsRebalance(Portfolio $portfolio): bool;
    public function rebalance(Portfolio $portfolio): void;

}
