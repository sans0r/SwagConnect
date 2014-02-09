<?php

namespace Shopware\Bepado\Components\CategoryQuery;

use Enlight_Components_Test_Plugin_TestCase;
use Bepado\SDK\Struct\Product;
use Shopware\Bepado\Components\BepadoFactory;

class Sw40QueryTest extends CategoryQueryTest
{
    protected function createQuery()
    {
        Shopware()->Bootstrap()->getResource('BepadoSDK');

        $factory = new BepadoFactory();

        if (!$factory->checkMinimumVersion('4.1.0')) {
            $this->markTestSkipped('This tests only run on Shopware 4.1.0 and greater');
        }

        return $factory->getShopware41CategoryQuery();
    }
}

