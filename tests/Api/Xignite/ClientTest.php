<?php declare(strict_types=1);

namespace TotalReturn\Api\Xignite;

use PHPUnit\Framework\TestCase;
use TotalReturn\AppTrait;
use TotalReturn\Service;

class ClientTest extends TestCase
{
    use AppTrait;

    public function testDividends(): void
    {
        $app = $this->createApp();

        /** @var Client $client */
        $client = $app->getServiceManager()->get(Service::XIGNITE_CLIENT);

        $dividends = $client->getDividends('VBMFX');

        $this->assertGreaterThan(0, count($dividends));

        foreach ($dividends as $dividend) {
            if ($dividend->getExDate() == new \DateTime('2018-02-01')) {
                $this->assertEquals(0.02235, round($dividend->getAmount(), 5));
            }
            if ($dividend->getExDate() == new \DateTime('2018-02-01')) {
                $this->assertEquals(0.02235, round($dividend->getAmount(), 5));
            }
        }
    }
}
