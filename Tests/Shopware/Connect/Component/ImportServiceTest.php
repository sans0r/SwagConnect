<?php

namespace Tests\ShopwarePlugins\Connect\Component;

use Shopware\Models\Category\Category;
use ShopwarePlugins\Connect\Components\CategoryExtractor;
use ShopwarePlugins\Connect\Components\CategoryResolver\AutoCategoryResolver;
use ShopwarePlugins\Connect\Components\ImportService;
use Tests\ShopwarePlugins\Connect\ConnectTestHelper;
use Shopware\Connect\Gateway\PDO;

class ImportServiceTest extends ConnectTestHelper
{
    /**
     * @var \ShopwarePlugins\Connect\Components\ImportService
     */
    private $importService;

    private $connectAttributeRepository;

    /**
     * @var \Shopware\Components\Model\ModelManager
     */
    private $manager;

    private $remoteCategoryRepository;

    private $categoryRepository;

    private $articleRepository;

    public static function setUpBeforeClass()
    {
        $conn = Shopware()->Db();
        $conn->delete('sw_connect_shop_config', array('s_shop = ?' => '_price_type'));
        $conn->insert('sw_connect_shop_config', array('s_shop' => '_price_type', 's_config' => 3));
    }

    public function setUp()
    {
        $this->manager = Shopware()->Models();

        $this->categoryRepository = $this->manager->getRepository('Shopware\Models\Category\Category');
        $this->remoteCategoryRepository = $this->manager->getRepository('Shopware\CustomModels\Connect\RemoteCategory');
        $this->connectAttributeRepository = Shopware()->Models()->getRepository('Shopware\CustomModels\Connect\Attribute');
        $this->articleRepository = $this->manager->getRepository('Shopware\Models\Article\Article');
        $autoCategoryResolver = new AutoCategoryResolver(
            $this->manager,
            $this->categoryRepository,
            $this->remoteCategoryRepository
        );

        $this->importService = new ImportService(
            $this->manager,
            Shopware()->Container()->get('multi_edit.product'),
            $this->remoteCategoryRepository,
            $this->articleRepository,
            $this->remoteCategoryRepository,
            $this->manager->getRepository('Shopware\CustomModels\Connect\ProductToRemoteCategory'),
            $autoCategoryResolver,
            new CategoryExtractor(
                $this->connectAttributeRepository,
                $autoCategoryResolver,
                new PDO(Shopware()->Db()->getConnection())
            )
        );
    }

    public function testUnAssignArticleCategories()
    {
        Shopware()->Db()->exec("DELETE FROM `s_plugin_connect_categories`");

        $sourceIds = $this->insertOrUpdateProducts(3, false);

        // find articles by sourceId
        $connectAttributes = $this->connectAttributeRepository->findBy(array('sourceId' => $sourceIds));

        // map buecher category to some local category
        $localCategory = $this->categoryRepository->find(6);
        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(array('categoryKey' => '/bücher'));
        $remoteCategory->setLocalCategory($localCategory);
        $this->manager->persist($remoteCategory);
        $this->manager->flush();

        // assign local category to products
        $articleIds = array();
        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        foreach ($connectAttributes as $connectAttribute) {
            $article = $connectAttribute->getArticle();
            $article->addCategory($localCategory);

            $attribute = $article->getAttribute();
            $attribute->setConnectMappedCategory(true);

            $this->manager->persist($attribute);
            $this->manager->persist($article);

            $articleIds[] = $article->getId();
        }

        $this->manager->flush();

        // call unAssignArticleCategories
        $this->importService->unAssignArticleCategories($articleIds);
        $db = Shopware()->Db();
        $this->assertEquals(
            0,
            $db->query('SELECT COUNT(*) FROM s_articles_categories WHERE articleID IN (' . implode(", ", $articleIds) . ')')->fetchColumn()
        );

        $this->assertEquals(
            0,
            $db->query('SELECT COUNT(*) FROM s_articles_categories_ro WHERE articleID IN (' . implode(", ", $articleIds) . ')')->fetchColumn()
        );

        $this->manager->clear();
        /** @var \Shopware\Models\Article\Article $article */
        foreach ($this->articleRepository->findBy(array('id' => $articleIds)) as $article) {
            // check connect_mapped_category flag, must be null
            $this->assertNull($article->getAttribute()->getConnectMappedCategory());

            // check article->getCategories for each article, it should be an empty array
            $this->assertEmpty($article->getCategories());
        }
    }

    public function testFindRemoteArticleIdsByCategoryId()
    {
        // insert 3 articles
        $sourceIds = $this->insertOrUpdateProducts(3, false);

        // find articles by sourceId
        $connectAttributes = $this->connectAttributeRepository->findBy(array('sourceId' => $sourceIds));

        // map them to local category
        // map buecher category to local category
        $parentCategory = $this->categoryRepository->find(3);
        $localCategory = new Category();
        $localCategory->setName('MassImport #'. rand(1, 999999999));
        $localCategory->setParent($parentCategory);
        $this->manager->persist($localCategory);

        /** @var \Shopware\CustomModels\Connect\RemoteCategory $remoteCategory */
        $remoteCategory = $this->remoteCategoryRepository->findOneBy(array('categoryKey' => '/bücher'));
        $remoteCategory->setLocalCategory($localCategory);
        $this->manager->persist($remoteCategory);
        $this->manager->flush();

        // assign local category to products
        $articleIds = array();
        /** @var \Shopware\CustomModels\Connect\Attribute $connectAttribute */
        foreach ($connectAttributes as $connectAttribute) {
            $article = $connectAttribute->getArticle();
            $article->addCategory($localCategory);

            $attribute = $article->getAttribute();
            $attribute->setConnectMappedCategory(true);

            $this->manager->persist($attribute);
            $this->manager->persist($article);

            $articleIds[] = $article->getId();
        }

        $this->manager->flush();

        //call findRemoteArticleIdsByCategoryId
        // and compare returned array of ids
        $assignedArticleIds = $this->importService->findRemoteArticleIdsByCategoryId($localCategory->getId());

        $this->assertEquals($articleIds, $assignedArticleIds);
    }
}
