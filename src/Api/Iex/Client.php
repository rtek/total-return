<?php declare(strict_types=1);

namespace TotalReturn\Api\Iex;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use TotalReturn\Market\Symbol;

class Client
{
    protected $client;

    public function __construct()
    {
        $this->client = new Guzzle([
            'base_uri' => 'https://api.iextrading.com/1.0/',
            RequestOptions::VERIFY => realpath('data/cabundle.pem'),
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    public function getPrice(string $symbol)
    {
        $resp = $this->client->get("stock/$symbol/price");
        return $this->extractJson($resp);
    }

    public function getOhlc(string $symbol): array
    {
        $resp = $this->client->get("stock/$symbol/ohlc");
        return $this->extractJson($resp);
    }

    /**
     * @param string $symbol
     * @param string $period
     * @return Dividend[]
     */
    public function getDividends(string $symbol, string $period): array
    {
        $resp = $this->client->get("stock/$symbol/dividends/$period");

        $symbol = new Symbol($symbol);

        return array_map(function ($item) use ($symbol) {
            return new Dividend($symbol, $item);
        }, $this->extractJson($resp));
    }

    public function getChart(string $symbol, $time): array
    {
        $resp = $this->client->get("stock/$symbol/chart/$time");
        return $this->extractJson($resp);
    }

    protected function extractJson(ResponseInterface $resp)
    {
        if ($resp->getStatusCode() !== 200) {
            echo $resp->getBody()->getContents();
            throw new \LogicException('Did not get 200');
        }
        return json_decode($resp->getBody()->getContents(), true);
    }
}
