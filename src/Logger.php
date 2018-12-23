<?php declare(strict_types=1);

namespace TotalReturn;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use TotalReturn\Portfolio\Portfolio;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    protected $lastDay;

    public function log($level, $message, array $context = []): void
    {
        //this is a hack - move day into logger
        $day = substr($message, 0, 14);
        if($day === $this->lastDay) {
            $message = str_replace($day, str_repeat(' ', 14), $message);
        }
        $this->lastDay = $day;

        echo $message."\n";
        ob_flush();
        flush();
    }

    public function debugAllocation(Portfolio $p): void
    {
        $alloc = $p->getAllocation();
        $parts =  array_map(function ($k, $v) {
            return sprintf('%s %.1f%%', $k, $v*100);
        }, array_keys($alloc), $alloc);
        $this->debug(
            sprintf(
                '%s %10.1f % 4s            AA %s',
            $p->getTimeline()->formatToday(),
            $p->getValue(),
            $p->getCashSymbol()->getTicker(),
            implode(' ', $parts)
        )
        );
    }
}
