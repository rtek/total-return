<?php declare(strict_types=1);

namespace TotalReturn\KeyValue;

use TotalReturn\Market\Symbol;

class Ns
{
    public static function id($id): string
    {
        if ($id instanceof \DateTime) {
            return $id->format('Y-m-d');
        }

        if ($id instanceof Symbol) {
            return $id->getTicker();
        }

        return (string)$id;
    }

    public static function daily(Symbol $symbol): string
    {
        return "daily-$symbol";
    }

    public static function dividend(Symbol $symbol): string
    {
        return "dividend-$symbol";
    }

    public static function dividendUpdate(): string
    {
        return 'dividend-update';
    }

    public static function split(Symbol $symbol): string
    {
        return "split-$symbol";
    }

    public static function splitUpdate(): string
    {
        return 'split-update';
    }

    public static function tradeDaysUpdate(): string
    {
        return 'trade-days-update';
    }
}
