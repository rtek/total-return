<?php

namespace TotalReturn;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    public function log($level, $message, array $context = [])
    {
        echo $message."\n";
        ob_flush();
        flush();
    }


}
