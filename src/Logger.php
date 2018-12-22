<?php declare(strict_types=1);

namespace TotalReturn;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use TotalReturn\Portfolio\Portfolio;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = []): void
    {
        echo $message."\n";
        ob_flush();
        flush();
    }

    public function debugAllocation(Portfolio $p): void
    {
        $alloc = $p->getAllocation();
        $parts =  array_map(function($k, $v) {
            return sprintf('%s %.1f%%', $k, $v*100);
        }, array_keys($alloc), $alloc);
        $this->debug($p->getTimeline()->formatToday() .':                  '. implode(' ',$parts));
    }
}
