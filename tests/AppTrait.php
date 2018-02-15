<?php


namespace TotalReturn;


use Zend\ConfigAggregator\PhpFileProvider;

trait AppTrait
{
    public function createApp()
    {
        return App::create([new PhpFileProvider('tests/_files/config/{,*.}{global,local}.php')]);
    }
}
