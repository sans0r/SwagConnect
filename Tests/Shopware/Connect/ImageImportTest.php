<?php

namespace Tests\ShopwarePlugins\Connect;

use Shopware\Models\Article\Supplier;

class ImageImportTest extends ConnectTestHelper
{
    public function testGetProductsNeedingImageImport()
    {
        $ids = $this->insertOrUpdateProducts(10, false);

        $result = $this->getImageImport()->getProductsNeedingImageImport();

        $this->assertNotEmpty($result);
    }


    public function testHasArticleMainImage()
    {
        $result = $this->getImageImport()->hasArticleMainImage(2);

        $this->assertTrue($result);
    }

    public function testImportImagesForArticle()
    {
        $images = array();
        for ($i=0; $i<10; $i++) {
            $images[] = self::IMAGE_PROVIDER_URL . '?' . $i;
        }

        /** @var \Shopware\Models\Article\Article $model */
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $model->getImages()->clear();
        $this->getImageImport()->importImagesForArticle($images, $model);

        // reload article model after image import otherwise model contains only old images
        $model = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $this->assertEquals(10, $model->getImages()->count());
    }

    public function testImportDifferentImagesForEachVariant()
    {
        $articleImages = array(
            'http://loremflickr.com/200/200?0'
        );
        $variantImages = array();
        for ($i=1; $i<10; $i++) {
//            $images[] = self::IMAGE_PROVIDER_URL . '?' . $i;
//            $images[] = 'http://pipsum.com/200x100.jpg' . '?' . $i;
            $variantImages[] = 'http://loremflickr.com/200/100' . '?' . $i;
        }

        $expectedImages = $variantImages; //todo@sb: why?!?!
        /** @var \Shopware\Models\Article\Article $article */
        $article = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $article->getImages()->clear();

        $this->getImageImport()->importImagesForArticle($articleImages, $article);

        /** @var \Shopware\Models\Article\Detail $detail */
        foreach ($article->getDetails() as $detail) {
            //clear all imported images before test
            foreach ($detail->getImages() as $image) {
                Shopware()->Models()->remove($image);
            }
            Shopware()->Models()->flush();
            $this->getImageImport()->importImagesForDetail(array_splice($variantImages, 0, 3), $detail);
        }

        // reload article model after image import otherwise model contains only old images
        $article = Shopware()->Models()->find('Shopware\Models\Article\Article', 2);
        $this->assertEquals(10, $article->getImages()->count());

        $articleImages = $article->getImages();
        /** @var \Shopware\Models\Article\Image $mainImage */
        $mainImage = $articleImages[0];
        $this->assertEmpty($mainImage->getMappings());

        unset($articleImages[0]);
        foreach ($articleImages as $image) {
            /** @var \Shopware\Models\Article\Image $image */
            $this->assertNotEmpty($image->getMappings());
        }

        /** @var \Shopware\Models\Article\Detail $detail */

        foreach ($article->getDetails() as $detail) {
            $detailImages = $detail->getImages();
            $this->assertEquals(3, $detail->getImages()->count());
        }
    }

    public function testImportImagesForSupplier()
    {
        /* @var Supplier $supplier*/
        $supplier = Shopware()->Models()->find('Shopware\Models\Article\Supplier', 1);
        $supplier->setImage('');

        $this->getImageImport()->importImageForSupplier(self::IMAGE_PROVIDER_URL, $supplier);

        $this->assertNotEmpty($supplier->getImage());
    }

}