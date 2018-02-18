<?php declare(strict_types=1);

namespace TotalReturn;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;

class Dev extends TestCase
{
    public function testDev(): void
    {
        $url = 'https://www.xignite.com/xGlobalHistorical.json/GetCashDividendHistory?IdentifierType=Symbol&Identifier=vbmfx&StartDate=1/1/1900&EndDate=2/17/18&_token=fd1f4a4e836e3c67486c178d82228ff7d616c6af732d8d8de92c602095f9e20e69861778b09210655cecd06cce05c2840d48a20b&_token_userid=122&_=1518916739824';

        $http = new Client();
        $resp = $http->get($url, [
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.167 Safari/537.36',
            ],
        ]);

        $json = json_decode($resp->getBody()->getContents(), true);

        var_dump($json);
    }
}
