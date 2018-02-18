<?php declare(strict_types=1);

namespace TotalReturn;

use PHPUnit\Framework\TestCase;
use TotalReturn\Market\Symbol;
use Zend\ConfigAggregator\PhpFileProvider;

class MarketDataTest extends TestCase
{
    public function testDividends(): void
    {
        $app = App::create([new PhpFileProvider('tests/_files/config/{,*.}{global,local}.php')]);

        $sm = $app->getServiceManager();

        /** @var MarketData $md */
        $md = $sm->get(Service::MARKET_DATA);
        $md->setLogger($logger = new Logger());

        $this->assertNotNull($md->findDividend(Symbol::lookup('AAPL'), new \DateTime('2018-02-09')));

        $this->assertNotNull($md->findDividend($vbmfx = Symbol::lookup('VBMFX'), new \DateTime('2017-12-22')));
        $this->assertNotNull($md->findDividend($vbmfx, new \DateTime('2017-10-02')));
    }
}
