<?php declare(strict_types=1);

namespace TotalReturn;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = []): void
    {
        echo $message."\n";
        ob_flush();
        flush();
    }
}
