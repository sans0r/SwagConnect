<?php

namespace ShopwarePlugins\Connect\Subscribers;

use ShopwarePlugins\Connect\Components\Config;
use ShopwarePlugins\Connect\Components\Utils;
use ShopwarePlugins\Connect\Components\ConnectExport;
use ShopwarePlugins\Connect\Components\Validator\ProductAttributesValidator\ProductsAttributesValidator;

/**
 * Handles article lifecycle events in order to automatically update/delete products to/from connect
 *
 * Class Lifecycle
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Lifecycle extends BaseSubscriber
{

    public function getSubscribedEvents()
    {
        return array(
            'Shopware\Models\Article\Article::postPersist' => 'onUpdateArticle',
            'Shopware\Models\Article\Article::postUpdate' => 'onUpdateArticle',
            'Shopware\Models\Article\Detail::postUpdate' => 'onUpdateArticle',
            'Shopware\Models\Article\Article::preRemove' => 'onDeleteArticle',
            'Shopware\Models\Article\Detail::preRemove' => 'onDeleteDetail',
            'Shopware\Models\Order\Order::postUpdate' => 'onUpdateOrder',
        );
    }

    /**
     * @return ConnectExport
     */
    public function getConnectExport()
    {
        return new ConnectExport(
            $this->getHelper(),
            $this->getSDK(),
            Shopware()->Models(),
            new ProductsAttributesValidator(),
            new Config(Shopware()->Models())
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateOrder(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Order\Order $order */
        $order = $eventArgs->get('entity');

        $attribute = $order->getAttribute();
        if (!$attribute || !$attribute->getConnectShopId()) {
            return;
        }

        // Compute the changeset and return, if orderStatus did not change
        $changeSet = $eventArgs->get('entityManager')->getUnitOfWork()->getEntityChangeSet($order);
        if (!isset($changeSet['orderStatus'])) {
            return;
        }


        $orderStatusMapper = new Utils\OrderStatusMapper();
        $orderStatus = $orderStatusMapper->getOrderStatusStructFromOrder($order);

        try {
            $this->getSDK()->updateOrderStatus($orderStatus);
        } catch (\Exception $e) {
            // if sn is not available, proceed without exception
        }
    }

    /**
     * Callback function to delete an product from connect
     * after it is going to be deleted locally
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onDeleteArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        $entity = $eventArgs->get('entity');
        $this->getConnectExport()->syncDeleteArticle($entity);
    }

    /**
     * Callback function to delete product detail from connect
     * after it is going to be deleted locally
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onDeleteDetail(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \Shopware\Models\Article\Detail $entity */
        $entity = $eventArgs->get('entity');
        if ($entity->getKind() == 1) {
            $article = $entity->getArticle();
            $this->getConnectExport()->setDeleteStatusForVariants($article, 'delete');
        } else {
            $this->getConnectExport()->syncDeleteDetail($entity);
        }
    }

    /**
     * Callback method to update changed connect products
     *
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onUpdateArticle(\Enlight_Event_EventArgs $eventArgs)
    {
        /** @var \ShopwarePlugins\Connect\Components\Config $configComponent */
        $configComponent = new Config(Shopware()->Models());

        $autoUpdate = $configComponent->getConfig('autoUpdateProducts', 1);
        if (!$autoUpdate) {
            return;
        }

        $entity = $eventArgs->get('entity');

        if (!$entity instanceof \Shopware\Models\Article\Article
            && !$entity instanceof \Shopware\Models\Article\Detail
        ) {
            return;
        }

        $id = $entity->getId();
        $className = get_class($entity);
        $model = Shopware()->Models()->getRepository($className)->find($id);
        // Check if we have a valid model
        if (!$model) {
            return;
        }

        // Check if entity is a connect product
        $attribute = $this->getHelper()->getConnectAttributeByModel($model);
        if (!$attribute) {
            return;
        }

        // todo@dn: Check logic
        $status = $attribute->getExportStatus();
        $shopId = $attribute->getShopId();
        if (empty($status) || !empty($shopId)) {
            return;
        }

        // if status is delete,
        // article should not be updated in connect
        if ($status == 'delete') {
            return;
        }

        // Mark the product for connect update
        try {
            if ($model instanceof \Shopware\Models\Article\Detail) {
                $this->generateChangesForDetail($model, $autoUpdate);
            } elseif ($model instanceof \Shopware\Models\Article\Article){
                $this->generateChangesForArticle($model, $autoUpdate);
            }
        } catch (\Exception $e) {
            // If the update fails due to missing requirements
            // (e.g. category assignment), continue without error
        }
    }

    private function generateChangesForDetail(\Shopware\Models\Article\Detail $detail, $autoUpdate)
    {
        $attribute = $this->getHelper()->getConnectAttributeByModel($detail);

        if ($autoUpdate == 1) {
            $this->getConnectExport()->export(
                array($attribute->getSourceId())
            );
        } elseif ($autoUpdate == 2) {
            $attribute->setCronUpdate(true);
            Shopware()->Models()->persist($attribute);
            Shopware()->Models()->flush();
        }
    }

    private function generateChangesForArticle(\Shopware\Models\Article\Article $article, $autoUpdate)
    {
        if ($autoUpdate == 1) {
            $sourceIds = Shopware()->Db()->fetchCol(
                'SELECT source_id FROM s_plugin_connect_items WHERE article_id = ?',
                array($article->getId())
            );

            $this->getConnectExport()->export($sourceIds);
        } elseif ($autoUpdate == 2) {
            Shopware()->Db()->update(
                's_plugin_connect_items',
                array('cron_update' => 1),
                array('article_id' => $article->getId())
            );
        }
    }
}