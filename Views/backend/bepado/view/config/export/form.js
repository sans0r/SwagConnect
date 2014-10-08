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
 * Shopware SwagBepado Plugin
 *
 * @category Shopware
 * @package Shopware\Plugins\SwagBepado
 * @copyright Copyright (c) shopware AG (http://www.shopware.de)
 */
//{namespace name=backend/bepado/view/main}
//{block name="backend/bepado/view/config/export/form"}
Ext.define('Shopware.apps.Bepado.view.config.export.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.bepado-config-export-form',

    border: false,
    layout: 'anchor',
    autoScroll: true,
    region: 'center',
    bodyPadding: 10,

    /**
     * Contains the field set defaults.
     */
    defaults: {
        labelWidth: 200,
        anchor: '100%'
    },

    snippets: {
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        productDescriptionFieldLabel: '{s name=config/export/product_description_field_label}Product description field{/s}',
        autoProductSync: '{s name=config/export/auto_product_sync_label}Automatically sync changed products to bepado{/s}',
        autoPlayedChanges: '{s name=config/export/changes_auto_played_label}Will autmatically sync changed bepado products to the bepado platform{/s}',
        emptyText: '{s name=config/export/empty_text_combo}Please choose{/s}',
        defaultCategory: '{s name=config/export/default_export_category}Default Export category{/s}',
        edit: '{s name=edit}Edit{/s}'
    },

    initComponent: function () {
        var me = this;

        me.items = me.createElements();
        me.dockedItems = [
            {
                xtype: 'toolbar',
                dock: 'bottom',
                ui: 'shopware-ui',
                cls: 'shopware-toolbar',
                items: me.getFormButtons()
            }
        ];

        me.exportConfigStore = Ext.create('Shopware.apps.Bepado.store.config.Export').load({
            callback: function() {
                me.populateForm();
            }
        });

        me.callParent(arguments);
    },

    /**
     * Returns form buttons, save and cancel
     * @returns Array
     */
    getFormButtons: function () {
        var me = this,
            buttons = ['->'];

        var saveButton = Ext.create('Ext.button.Button', {
            text: me.snippets.save,
            action: 'save-export-config',
            cls: 'primary'
        });

        var cancelButton = Ext.create('Ext.button.Button', {
            text: me.snippets.cancel,
            handler: function (btn) {
                btn.up('window').close();
            }
        });

        buttons.push(cancelButton);
        buttons.push(saveButton);

        return buttons;
    },

    /**
     * Creates the field set items
     * @return Array
     */
    createElements: function () {
        var me = this;

        var container = me.createProductContainer();

        me.priceMappingsFieldSet = Ext.create('Ext.form.FieldSet', {
            title: '{s name =config/export/priceConfiguration}Price configuration{/s}',
            disabled: false,
            items: [
                {
                    xtype: 'label',
                    html: '{s name=config/export/label/priceDescription}Here you can configure the prices that will be exported as your product price. You can configure the  »customer« price and the »merchant« price. Foreach each of these prices you can configure from which price group the value should be read and which price field should be used.<br><br>{/s}'
                },
                me.createPriceField('price'),
                me.createPriceField('purchasePrice')
            ]
        });

        Ext.getStore('export.List').load({
            callback: function(records, operation) {
                if (!operation.wasSuccessful()) {
                    return;
                }
                // if there is exported product
                // pricing mapping should be disabled
                for (var i=0;i<records.length;i++) {
                    var exportStatus = records[i].get('exportStatus');
                    if ( exportStatus == 'update' || exportStatus == 'insert' || exportStatus == 'error') {
                        me.priceMappingsFieldSet.setDisabled(true);
                    }

                }
            }
        });

        return [
            {
                xtype: 'bepado-config-export-description'
            },
            container,
            me.priceMappingsFieldSet
        ];
    },

    /**
     * Creates a price config fieldcontainer for price or purchasePrice
     *
     * @return Object
     */
    createPriceField: function (type) {
        var me = this,
            fieldLabel,
            dataIndexCustomerGroup,
            dataIndexField,
            helpText;

        if (type == 'price') {
            fieldLabel = '{s name=config/price/price}Price{/s}';
            dataIndexCustomerGroup = 'priceGroupForPriceExport';
            dataIndexField = 'priceFieldForPriceExport';
            helpText = '{s name=config/export/help/price}Configure, which price field of which customer group should be exported as the product\'s end user price{/s}';
        } else if (type == 'purchasePrice') {
            fieldLabel = '{s name=config/price/purchasePrice}PurchasePrice{/s}';
            dataIndexCustomerGroup = 'priceGroupForPurchasePriceExport';
            dataIndexField = 'priceFieldForPurchasePriceExport';
            helpText = '{s name=config/export/help/purchasePrice}Configure, which price field of which customer group should be exported as the product\'s merchant price{/s}';
        } else {
            return { };
        }

        return {
            fieldLabel: fieldLabel,
            xtype: 'fieldcontainer',
            layout: 'hbox',
            items: [
                {
                    xtype: 'combobox',
                    queryMode: 'remote',
                    editable: false,
                    name: dataIndexCustomerGroup,
                    allowBlank: false,
                    displayField: 'name',
                    valueField: 'key',
                    store: Ext.create('Shopware.apps.Bepado.store.config.CustomerGroup', { }).load(),
                    supportText: '{s name=config/export/support/customer}customer group{/s}'
                },
                {
                    xtype: 'combobox',
                    name: dataIndexField,
                    store: Ext.create('Ext.data.Store', {
                        fields: ['field', 'name'],
                        data: me.getPriceData()
                    }),
                    queryMode: 'local',
                    editable: false,
                    allowBlank: false,
                    displayField: 'name',
                    valueField: 'field',
                    helpText: helpText,
                    supportText: '{s name=config/export/support/price}price field{/s}'

                }
            ]
        };
    },

    /**
     * Returns allowed price columns
     *
     * @returns Array
     */
    getPriceData: function () {
        var me = this,
            columns = [
                { field: 'basePrice', name: '{s namespace=backend/article/view/main name=detail/price/base_price}{/s}' },
                { field: 'price', name: '{s namespace=backend/article/view/main name=detail/price/price}{/s}' },
                { field: 'pseudoPrice', name: '{s namespace=backend/article/view/main name=detail/price/pseudo_price}{/s}' }
            ];

        return columns;
    },

    /**
     * Populate export config form
     */
    populateForm: function () {
        var me = this;

        me.loadRecord(me.getRecord());
    },

    getRecord: function () {
        var me = this,
            record = me.exportConfigStore.getAt(0);

        if (!record) {
            record = Ext.create('Shopware.apps.Bepado.model.config.Export');
        }

        return record;
    },

    createProductContainer: function () {
        var me = this,
            defaultExportCategory = Ext.create('Ext.form.TextField',{
                name: 'defaultExportCategory',
                readOnly: true,
                flex: 4
            });

        return Ext.create('Ext.container.Container', {
            columnWidth: 1,
            padding: '0 0 20 0',
            layout: 'fit',
            border: false,
            items: [
                {
                    xtype: 'combobox',
                    fieldLabel: me.snippets.productDescriptionFieldLabel,
                    emptyText: me.snippets.emptyText,
                    name: 'alternateDescriptionField',
                    store: new Ext.data.SimpleStore({
                        fields: [ 'value', 'text' ],
                        data: [
                            ['attribute.bepadoProductDescription', 'attribute.bepadoProductDescription'],
                            ['a.description', 'Artikel-Kurzbeschreibung'],
                            ['a.descriptionLong', 'Artikel-Langbeschreibung']
                        ]
                    }),
                    queryMode: 'local',
                    displayField: 'text',
                    valueField: 'value',
                    editable: false,
                    labelWidth: me.defaults.labelWidth
                },
                {
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    fieldLabel: me.snippets.defaultCategory,
                    labelWidth: me.defaults.labelWidth,
                    items:[
                        defaultExportCategory,
                        Ext.create('Ext.button.Button', {
                            text: me.snippets.edit,
                            height: 27,
                            minWidth: 100,
                            bodyPadding: '0 0 0 3',
                            cls: 'primary',
                            handler: function (btn) {
                                Ext.create('Ext.window.Window', {
                                    title: me.snippets.defaultCategory,
                                    height: 100,
                                    width: 400,
                                    layout: 'fit',
                                    modal: true,
                                    items: [
                                        {
                                        xtype: 'base-element-selecttree',
                                        allowBlank: true,
                                        store: 'mapping.BepadoCategoriesExport',
                                        name: 'defaultExportCategoryCombo',
                                        labelWidth: me.defaults.labelWidth,
                                        listeners: {
                                            select: function (arg) {
                                                defaultExportCategory.setValue(arg.getValue());
                                                arg.up('window').close();
                                            }
                                        }
                                    }]
                                }).show();
                            }
                        })
                    ]
                }, {
                    xtype: 'fieldcontainer',
                    fieldLabel: me.snippets.autoProductSync,
                    defaultType: 'checkboxfield',
                    labelWidth: me.defaults.labelWidth,
                    items: [
                        {
                            boxLabel: me.snippets.autoPlayedChanges,
                            name: 'autoUpdateProducts',
                            inputValue: 1,
                            uncheckedValue: 0
                        }
                    ]
                }
            ]
        });
    }
});
//{/block}

