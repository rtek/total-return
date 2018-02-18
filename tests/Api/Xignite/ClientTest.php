<?php


namespace TotalReturn\Api\Xignite;


use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    public function testDividends()
    {
        $client = new Client();

        $dividends = $client->getDividends('VBMFX');

        foreach($dividends as $dividend) {
            if($dividend->getExDate() == new \DateTime('2018-02-01')) {
                $this->assertEquals(0.02235, round($dividend->getAmount(),5));
            }
            if($dividend->getExDate() == new \DateTime('2018-02-01')) {
                $this->assertEquals(0.02235, round($dividend->getAmount(),5));
            }
        }
    }
}
