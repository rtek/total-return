<?php declare(strict_types=1);

namespace TotalReturn\Portfolio\Rebalancer;

use TotalReturn\Portfolio\Portfolio;

interface RebalancerInterface
{
    public function setPortfolio(Portfolio $portfolio): void;
    public function needsRebalance(): bool;
    public function rebalance(): void;
}
