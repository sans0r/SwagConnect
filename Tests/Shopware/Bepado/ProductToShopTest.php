<?php

namespace Tests\Shopware\Bepado;


use Bepado\SDK\Struct\Product;
use Doctrine\Common\Util\Debug;
use Shopware\Bepado\Components\Config;
use Shopware\Bepado\Components\Gateway\ProductTranslationsGateway\PdoProductTranslationsGateway;
use Shopware\Bepado\Components\Marketplace\MarketplaceGateway;
use Shopware\Bepado\Components\ProductToShop;
use Shopware\Bepado\Components\VariantConfigurator;

class ProductToShopTest extends BepadoTestHelper
{
    /** @var  \Shopware\Bepado\Components\ProductToShop */
    private $productToShop;

    private $modelManager;

    public function setUp()
    {
        $this->modelManager = Shopware()->Models();
        $this->productToShop = new ProductToShop(
            $this->getHelper(),
            $this->modelManager,
            $this->getImageImport(),
            new Config($this->modelManager),
            new VariantConfigurator(
                $this->modelManager,
                new PdoProductTranslationsGateway(Shopware()->Db())
            ),
            new MarketplaceGateway($this->modelManager),
            new PdoProductTranslationsGateway(Shopware()->Db())
        );
    }

    public function testInsertArticle()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_bepado_items
              LEFT JOIN s_articles ON (s_plugin_bepado_items.article_id = s_articles.id)
              WHERE s_plugin_bepado_items.source_id = :sourceId',
            array('sourceId' => $product->sourceId)
        )->fetchColumn();

        $this->assertEquals(1, $articlesCount);
    }

    public function testInsertArticleTranslations()
    {
        $product = $this->getProduct();
        $this->productToShop->insertOrUpdate($product);
        $productRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Article');
        /** @var \Shopware\Models\Article\Article $productModel */
        $productModel = $productRepository->findOneBy(array('name' => $product->title));

        $articleTranslation = Shopware()->Db()->query(
            'SELECT objectdata
              FROM s_core_translations
              WHERE objectkey = :productId AND objectlanguage = 2 AND objecttype = :objectType',
            array('productId' => $productModel->getId(), 'objectType' => 'article')
        )->fetchColumn();

        $this->assertNotFalse($articleTranslation);
        $articleTranslation = unserialize($articleTranslation);
        $this->assertEquals($product->translations['en']->title, $articleTranslation['txtArtikel']);
        $this->assertEquals($product->translations['en']->longDescription, $articleTranslation['txtlangbeschreibung']);
        $this->assertEquals($product->translations['en']->shortDescription, $articleTranslation['txtshortdescription']);
    }

    public function testInsertVariantOptionsAndGroupsTranslations()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $groupRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Configurator\Group');
        $optionRepository = Shopware()->Models()->getRepository('Shopware\Models\Article\Configurator\Option');
        foreach ($variants as $variant) {
            foreach ($variant->translations as $translation) {
                // check configurator group translations
                foreach ($translation->variantLabels as $groupKey => $groupTranslation) {
                    $group = $groupRepository->findOneBy(array('name' => $groupKey));

                    $objectData = Shopware()->Db()->query(
                        'SELECT objectdata
                          FROM s_core_translations
                          WHERE objectkey = :groupId AND objectlanguage = 2 AND objecttype = :objectType',
                        array('groupId' => $group->getId(), 'objectType' => 'configuratorgroup')
                    )->fetchColumn();

                    $objectData = unserialize($objectData);
                    $this->assertEquals($groupTranslation, $objectData['name']);
                }

                foreach ($translation->variantValues as $optionKey => $optionTranslation) {
                    $option =  $optionRepository->findOneBy(array('name' => $optionKey));
                    $objectData = Shopware()->Db()->query(
                        'SELECT objectdata
                          FROM s_core_translations
                          WHERE objectkey = :optionId AND objectlanguage = 2 AND objecttype = :objectType',
                        array('optionId' => $option->getId(), 'objectType' => 'configuratoroption')
                    )->fetchColumn();

                    $objectData = unserialize($objectData);
                    $this->assertEquals($optionTranslation, $objectData['name']);
                }
            }
        }
    }

    public function testInsertVariants()
    {
        $variants = $this->getVariants();

        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $bepadoAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Bepado\Attribute')
            ->findOneBy(array('sourceId' => $variants[0]->sourceId));
        $article = $bepadoAttribute->getArticle();
        // check articles details count
        $this->assertEquals(4, count($article->getDetails()));
        // check configurator set
        $this->assertNotNull($article->getConfiguratorSet());
        // check configurator group
        $group = $this->modelManager
            ->getRepository('Shopware\Models\Article\Configurator\Group')
            ->findOneBy(array('name' => 'Farbe'));
        $this->assertNotNull($group);
        // check group options
        $groupOptionValues = $articleOptionValues = array('Weiss-Blau', 'Weiss-Rot', 'Blau-Rot', 'Schwarz-Rot');
        foreach ($group->getOptions() as $option) {
            $key = array_search($option->getName(), $groupOptionValues);
            if ($key !== false) {
                unset($groupOptionValues[$key]);
            }
        }
        $this->assertEmpty($groupOptionValues);
        // check configuration set options
        $this->assertEquals(4, count($article->getConfiguratorSet()->getOptions()));
        foreach ($article->getConfiguratorSet()->getOptions() as $option) {
            $key = array_search($option->getName(), $articleOptionValues);
            if ($key !== false) {
                unset($articleOptionValues[$key]);
            }
        }
        $this->assertEmpty($articleOptionValues);
    }

    public function testUpdateVariant()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        $newTitle = 'Massimport#updateVariant' . rand(1, 10000000);
        $newPrice = 22.48;
        $newPurchasePrice = 8.48;
        $newLongDesc = 'Updated bepado variant - long description';
        $newShortDesc = 'Updated bepado variant - short description';
        $newVat = 0.07;
        $variants[1]->title = $newTitle;
        $variants[1]->price = $newPrice;
        $variants[1]->purchasePrice = $newPurchasePrice;
        $variants[1]->longDescription = $newLongDesc;
        $variants[1]->shortDescription = $newShortDesc;
        $variants[1]->images[] = 'http://lorempixel.com/400/200?' . $variants[1]->sourceId;
        $variants[1]->vat = $newVat;

        $this->productToShop->insertOrUpdate($variants[1]);

        /** @var \Shopware\CustomModels\Bepado\Attribute $bepadoAttribute */
        $bepadoAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Bepado\Attribute')
            ->findOneBy(array('sourceId' => $variants[1]->sourceId));
        $this->assertEquals($newTitle, $bepadoAttribute->getArticle()->getName());
        $this->assertEquals($newLongDesc, $bepadoAttribute->getArticle()->getDescriptionLong());
        $this->assertEquals($newShortDesc, $bepadoAttribute->getArticle()->getDescription());
        /** @var \Shopware\Models\Article\Price[] $prices */
        $prices = $bepadoAttribute->getArticleDetail()->getPrices();

        $this->assertEquals($newPrice, $prices[0]->getPrice());
        $this->assertEquals($newPurchasePrice, $prices[0]->getBasePrice());
        $this->assertEquals(2, count($bepadoAttribute->getArticle()->getImages()));
        $this->assertEquals(7.00, $bepadoAttribute->getArticle()->getTax()->getTax());
    }

    public function testImportWithoutTitle()
    {
        $product = new Product();
        $this->assertEmpty($this->productToShop->insertOrUpdate($product));
    }

    public function testImportWithoutVendor()
    {
        $product = new Product();
        $this->assertEmpty($this->productToShop->insertOrUpdate($product));
    }

    public function testDelete()
    {
        $variants = $this->getVariants();
        // insert variants
        foreach ($variants as $variant) {
            $this->productToShop->insertOrUpdate($variant);
        }

        // test delete only one variant
        $this->productToShop->delete($variants[1]->shopId, $variants[1]->sourceId);

        $bepadoAttribute = $this->modelManager
            ->getRepository('Shopware\CustomModels\Bepado\Attribute')
            ->findOneBy(array('sourceId' => $variants[2]->sourceId));

        $article = $bepadoAttribute->getArticle();
        // check articles details count
        $this->assertEquals(3, count($article->getDetails()));

        // test delete article - main article variant
        $this->productToShop->delete($variants[0]->shopId, $variants[0]->sourceId);

        $articlesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_articles.id)
              FROM s_plugin_bepado_items
              LEFT JOIN s_articles ON (s_plugin_bepado_items.article_id = s_articles.id)
              WHERE s_plugin_bepado_items.source_id = :sourceId',
            array('sourceId' => $variants[0]->sourceId)
        )->fetchColumn();

        $this->assertEquals(0, $articlesCount);

        $attributesCount = Shopware()->Db()->query(
            'SELECT COUNT(s_plugin_bepado_items.id)
              FROM s_plugin_bepado_items
              WHERE s_plugin_bepado_items.article_id = :articleId',
            array('articleId' => $article->getId())
        )->fetchColumn();

        $this->assertEquals(2, $attributesCount);
    }
}
 