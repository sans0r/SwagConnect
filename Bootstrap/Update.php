<?php

namespace Shopware\Bepado\Bootstrap;

/**
 * Updates existing versions of the plugin
 *
 * Class Update
 * @package Shopware\Bepado\Bootstrap
 */
class Update
{

    /** @var \Shopware_Plugins_Backend_SwagBepado_Bootstrap  */
    protected $bootstrap;
    protected $version;

    public function __construct(\Shopware_Plugins_Backend_SwagBepado_Bootstrap $bootstrap, $version)
    {
        $this->bootstrap = $bootstrap;
        $this->version = $version;
    }

    public function run()
    {
        // When the dummy plugin is going to be installed, don't do the later updates
        if (version_compare($this->version, '0.0.1', '<=')) {
            return true;
        }

        // Force an SDK re-verify
        $this->reVerifySDK();

        // Migrate old attributes to bepado attributes
        $this->migrateAttributes();

        if (version_compare($this->version, '1.2.70', '<=')) {
            Shopware()->Db()->exec('ALTER TABLE `bepado_shop_config` CHANGE `s_config` `s_config` LONGBLOB NOT NULL;');
        }

        // Split category mapping into mapping for import and export
        $this->removeOldCategoryMapping();

        // A product does only have one bepado category mapped
        $this->changeProductsToOnlyHaveOneCategory();

        // Migration from shopware config to new config system
        $this->migrateConfigToBepadoConfig();

        $this->removePluginConfiguration();

        return true;
    }

    /**
     * Removes the plugin configuration - all config will now be done in the bepado plugin itself
     */
    public function removePluginConfiguration()
    {
        // Remove old productDescriptionField
        // removeElement does seem to have some issued, so using plain SQL here
        if (version_compare($this->version, '1.4.28', '>')) {
            return;
        }

        if (!$this->bootstrap->getId()) {
            return;
        }

        $formRepository = $this->bootstrap->Forms();
        $form = $formRepository->findOneBy(array(
            'pluginId' => $this->bootstrap->getId()
        ));

        Shopware()->Models()->remove($form);
        Shopware()->Models()->flush();
    }

    /**
     * Forces the SDK to re-verify the API key
     */
    public function reVerifySDK()
    {
        Shopware()->Db()->query('
            UPDATE bepado_shop_config
            SET s_config = ?
            WHERE s_shop = "_last_update_"
            LIMIT 1; ',
            array(time() - 8 * 60 * 60 * 24)
        );
    }

    /**
     * Migrates the old product attributes to bepado's own attribute table
     * @return string
     */
    public function migrateAttributes()
    {
        if (version_compare($this->version, '1.2.18', '>')) {
            return;
        }

            $sql = 'INSERT IGNORE INTO `s_plugin_bepado_items`
              (`article_id`, `article_detail_id`, `shop_id`, `source_id`, `export_status`, `export_message`, `categories`,
              `purchase_price`, `fixed_price`, `free_delivery`, `update_price`, `update_image`,
              `update_long_description`, `update_short_description`, `update_name`, `last_update`,
              `last_update_flag`)
            SELECT `articleID`, `articledetailsID`, `bepado_shop_id`, `bepado_source_id`, `bepado_export_status`,
            `bepado_export_message`, `bepado_categories`, `bepado_purchase_price`, `bepado_fixed_price`,
            `bepado_free_delivery`, `bepado_update_price`, `bepado_update_image`, `bepado_update_long_description`,
             `bepado_update_short_description`, `bepado_update_name`, `bepado_last_update`, `bepado_last_update_flag`
            FROM `s_articles_attributes`';
        Shopware()->Db()->exec($sql);

        $this->removeMyAttributes();
    }

    /**
     * Remove the old bepado category mapping
     */
    public function removeOldCategoryMapping()
    {
        if (version_compare($this->version, '1.4.8', '>')) {
            return;
        }

        Shopware()->Models()->removeAttribute(
            's_categories_attributes',
            'bepado', 'mapping'
        );
        Shopware()->Models()->generateAttributeModels(array(
            's_categories_attributes'
        ));
    }

    /**
     * Force products to only have one category mapping
     */
    public function changeProductsToOnlyHaveOneCategory()
    {
        if (version_compare($this->version, '1.4.11', '>')) {
            return;
        }

            try {
            $sql = 'ALTER TABLE `s_plugin_bepado_items` change `categories` `category` text;';
            Shopware()->Db()->exec($sql);
        } catch (\Exception $e) {
            // if table was already altered, ignore
        }

        // Get serialized categories -.-
        $sql = 'SELECT id, category FROM `s_plugin_bepado_items` WHERE `category` LIKE "%{%" OR `category` = "N;"';
        $rows = Shopware()->Db()->fetchAll($sql);

        // Build values array with unserialized categories
        $values = array();
        foreach ($rows as $row) {
            $category = unserialize($row['category']);
            if (!empty($category) && is_array($category)) {
                $category = array_pop($category);
            } else {
                $category = null;
            }
            $values[$row['id']] = $category;
        }

        // Update the category one by one. This is not optimal, but only affects a few beta testers
        Shopware()->Db()->beginTransaction();
        foreach ($values as $id => $category) {
            Shopware()->Db()->query('UPDATE `s_plugin_bepado_items` SET `category` = ? WHERE id = ? ',
                array(
                    $category,
                    $id
                ));
        }
        Shopware()->Db()->commit();
    }

    /**
     * @return mixed
     */
    public function migrateConfigToBepadoConfig()
    {
        if (version_compare($this->version, '1.4.24', '>')) {
            return;
        }

        Shopware()->Db()->exec('ALTER TABLE  `s_plugin_bepado_config` ADD  `shopId` INT( 11 ) NULL DEFAULT NULL;');
        Shopware()->Db()->exec('ALTER TABLE  `s_plugin_bepado_config` ADD  `groupName` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;');

        $this->bootstrap->registerMyLibrary();
        $configComponent = $this->bootstrap->getConfigComponents();

        $apiKey = $this->bootstrap->Config()->get('apiKey');
        if ($apiKey) {
            $configComponent->setConfig('apiKey', $apiKey, null, 'general');
        }

        $bepadoDebugHost = $this->bootstrap->Config()->get('bepadoDebugHost');
        if ($bepadoDebugHost) {
            $configComponent->setConfig('bepadoDebugHost', $bepadoDebugHost, null, 'general');
        }

        $configComponent->setConfig('importCreateCategories', $this->bootstrap->Config()->get('importCreateCategories'));
        $configComponent->setConfig('detailProductNoIndex', $this->bootstrap->Config()->get('detailProductNoIndex'), 1, 'general');
        $configComponent->setConfig('detailShopInfo', $this->bootstrap->Config()->get('detailShopInfo'), 1, 'general');
        $configComponent->setConfig('checkoutShopInfo', $this->bootstrap->Config()->get('checkoutShopInfo'), 1, 'general');
        $configComponent->setConfig('cloudSearch', $this->bootstrap->Config()->get('cloudSearch'), 0, 'general');
        $configComponent->setConfig('alternateDescriptionField', $this->bootstrap->Config()->get('alternateDescriptionField'), 'a.descriptionLong', 'export');
        $configComponent->setConfig('bepadoAttribute', $this->bootstrap->Config()->get('bepadoAttribute'), '19', 'general');
        $configComponent->setConfig('importImagesOnFirstImport', $this->bootstrap->Config()->get('importImagesOnFirstImport'), false, 'import');
        $configComponent->setConfig('autoUpdateProducts', $this->bootstrap->Config()->get('autoUpdateProducts'), 1, 'export');
        $configComponent->setConfig('overwriteProductName', $this->bootstrap->Config()->get('overwriteProductName'), 1, 'import');
        $configComponent->setConfig('overwriteProductPrice', $this->bootstrap->Config()->get('overwriteProductPrice'), 1, 'import');
        $configComponent->setConfig('overwriteProductImage', $this->bootstrap->Config()->get('overwriteProductImage'), 1, 'import');
        $configComponent->setConfig('overwriteProductShortDescription', $this->bootstrap->Config()->get('overwriteProductShortDescription'), 1, 'import');
        $configComponent->setConfig('overwriteProductLongDescription', $this->bootstrap->Config()->get('overwriteProductLongDescription'), 1, 'import');
        $configComponent->setConfig('logRequest', $this->bootstrap->Config()->get('logRequest'), 0, 'general');
    }


    /**
     * Remove old product attributes
     */
    public function removeMyAttributes()
    {
        /** @var \Shopware\Components\Model\ModelManager $modelManager */
        $modelManager = Shopware()->Models();


        try {
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'shop_id'
            );
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'source_id'
            );
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'export_status'
            );
            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'export_message'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'categories'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'purchase_price'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'fixed_price'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'free_delivery'
            );


            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'update_price'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'update_image'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'update_long_description'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'update_short_description'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'update_name'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'last_update'
            );

            $modelManager->removeAttribute(
                's_articles_attributes',
                'bepado', 'last_update_flag'
            );

            $modelManager->generateAttributeModels(array(
                's_articles_attributes',
            ));
        } catch (\Exception $e) {
        }

    }

}