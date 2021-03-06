/**
 * Shopware 4
 * Copyright © shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */
/**
 * Shopware SwagConnect Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagConnect
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{block name="backend/connect/model/config/price_group"}
Ext.define('Shopware.apps.Connect.model.config.PriceGroup', {
    extend: 'Ext.data.Model',

    fields: [
        //{block name="backend/connect/model/config/price_group/fields"}{/block}
        { name: 'field', type: 'string' },
        { name: 'name', type: 'string' },
        { name: 'available', type: 'boolean' },
        { name: 'price', type: 'boolean' },
        { name: 'priceAvailable', type: 'boolean' },
        { name: 'priceConfiguredProducts', type: 'integer'},
        { name: 'basePrice', type: 'boolean' },
        { name: 'basePriceAvailable', type: 'boolean' },
        { name: 'basePriceConfiguredProducts', type: 'integer'},
        { name: 'pseudoPrice', type: 'boolean' },
        { name: 'pseudoPriceAvailable', type: 'boolean' },
        { name: 'pseudoPriceConfiguredProducts', type: 'integer'},
        { name: 'productCount', type: 'integer'}
    ]
});
//{/block}
