<?php


namespace TotalReturn\Api\Av;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class Client
{
    protected $client;

    protected $key;

    public function __construct($key)
    {
        $this->key = $key;

        $this->client = new Guzzle([
            'base_uri' => 'https://www.alphavantage.co',
            RequestOptions::VERIFY => realpath('data/cabundle.pem'),
            RequestOptions::HEADERS => [
                'Accept' => 'application/json'
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    public function getDaily(string $symbol, array $params = [])
    {
        return $this->query(array_merge($params, [
            'function' => 'TIME_SERIES_DAILY',
            'symbol' => $symbol
        ]));
    }

    public function getDailyAdjusted(string $symbol, array $params = [])
    {
        return $this->query(array_merge($params, [
            'function' => 'TIME_SERIES_DAILY_ADJUSTED',
            'symbol' => $symbol
        ]));
    }


    protected function query($params)
    {
        $resp = $this->client->get('query', $opts = [
            RequestOptions::QUERY => array_merge([
                'apikey' => $this->key
            ], $params)
        ]);

        return $this->extractJson($resp);
    }

    protected function extractJson(ResponseInterface $resp)
    {
        if($resp->getStatusCode() !== 200) {
            echo $resp->getBody()->getContents();
            throw new \LogicException('Did not get 200');
        }
        $json = json_decode($resp->getBody()->getContents(), true);

        if($error = $json['Error Message'] ?? false) {
            throw new \LogicException($error);
        }

        return $json;
    }
}
