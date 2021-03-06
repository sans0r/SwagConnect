<?php

namespace ShopwarePlugins\Connect\Components\Utils;

/**
 * Util to check orders for connect products
 *
 * Class ConnectOrderUtil
 * @package ShopwarePlugins\Connect\Components\Utils
 */
class ConnectOrderUtil
{
    const ORDER_STATUS_ERROR = 'sc_error';

    /**
     * Returns a list of connect orders, their shop_id and the remote order_id
     * @param $orderIds
     * @return mixed
     */
    function getRemoteConnectOrders($orderIds) {
        // This will apply for the fromShop
        $sql = 'SELECT orderID, connect_shop_id, connect_order_id
        FROM s_order_attributes
        WHERE orderID IN (' . implode(', ', $orderIds) . ')
        AND connect_shop_id IS NOT NULL
        ';
        return  Shopware()->Db()->fetchAll($sql);
    }

    /**
     * Does a given order have connect products?
     *
     * @param $orderId
     * @return bool
     */
    function hasRemoteOrderConnectProducts($orderId)
    {
        $orders = $this->getRemoteConnectOrders(array($orderId));
        return !empty($orders);
    }

    /**
     * Returns a list of connect orders and their shop_id
     *
     * @param $orderIds
     * @return mixed
     */
    function getLocalConnectOrders($orderIds)
    {
        // This will apply for orders with remote connect products in it
        $sql = 'SELECT oa.orderID, bi.shop_id as connect_shop_id,  "remote" as connect_order_id

        FROM s_order_attributes oa

        INNER JOIN s_order_details od
        ON od.orderID = oa.orderID

        INNER JOIN s_articles_details ad
        ON ad.articleID = od.articleID
        AND ad.kind=1

        INNER JOIN s_plugin_connect_items bi
        ON bi.article_detail_id=ad.id
        AND bi.shop_id IS NOT NULL

        WHERE oa.orderID In (' . implode(', ', $orderIds) . ')
        ';

        return Shopware()->Db()->fetchAll($sql);
    }

    /**
     * Does a given order have connect products?
     *
     * @param $orderId
     * @return bool
     */
    function hasLocalOrderConnectProducts($orderId)
    {
        $orders = $this->getLocalConnectOrders(array($orderId));
        return !empty($orders);
    }
}