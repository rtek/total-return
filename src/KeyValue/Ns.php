<?php

namespace TotalReturn\KeyValue;

use TotalReturn\Market\Symbol;

class Ns
{
    static public function id($id): string
    {
        if($id instanceof \DateTime) {
            return $id->format('Y-m-d');
        }

        if($id instanceof Symbol) {
            return $id->getTicker();
        }

        return (string)$id;
    }

    static public function daily(Symbol $symbol): string
    {
        return "daily-$symbol";
    }

    static public function dividend(Symbol $symbol): string
    {
        return "dividend-$symbol";
    }

    static public function dividendUpdate(): string
    {
        return 'dividend-update';
    }

    static public function split(Symbol $symbol): string
    {
        return "split-$symbol";
    }

    static public function splitUpdate(): string
    {
        return 'split-update';
    }


}
