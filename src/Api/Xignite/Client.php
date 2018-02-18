<?php declare(strict_types=1);

namespace TotalReturn\Api\Xignite;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use TotalReturn\Market\Symbol;

class Client
{
    /** @var Guzzle */
    protected $client;

    /** @var string */
    protected $token;

    /** @var string */
    protected $userId;

    public function __construct(string $token, int $userId)
    {
        $this->token = $token;
        $this->userId = $userId;

        $this->client = new Guzzle([
            'base_uri' => 'https://www.xignite.com/',
            RequestOptions::VERIFY => realpath('data/cabundle.pem'),
            RequestOptions::HEADERS => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.167 Safari/537.36',
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * @param string $ticker
     * @return Dividend[]
     */
    public function getDividends(string $ticker): array
    {
        $resp = $this->client->get('xGlobalHistorical.json/GetCashDividendHistory', [
            RequestOptions::QUERY => [
            'IdentifierType' => 'Symbol',
            'Identifier' => 'vbmfx',
            'StartDate' => '1/1/1900',
            'EndDate' => date('m/d/y'),
            '_token' => $this->token,
            '_token_userid' => $this->userId,
            '_' => strtotime('now'),
            ],
        ]);

        $symbol = Symbol::lookup($ticker);
        $json = $this->extractJson($resp);

        return array_map(function ($item) use ($symbol) {
            return new Dividend($symbol, $item);
        }, $json['Dividends']);
    }

    protected function extractJson(ResponseInterface $resp)
    {
        if ($resp->getStatusCode() !== 200) {
            echo $resp->getBody()->getContents();
            throw new \LogicException('Did not get 200');
        }
        return json_decode((string)$resp->getBody(), true);
    }
}
