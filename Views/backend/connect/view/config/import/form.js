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
//{namespace name=backend/connect/view/main}
//{block name="backend/connect/view/config/import/form"}
Ext.define('Shopware.apps.Connect.view.config.import.Form', {
    extend: 'Ext.form.Panel',
    alias: 'widget.connect-config-import-form',

    border: false,
    layout: 'anchor',
    autoScroll: true,
    region: 'center',
    bodyPadding: 10,

    /**
     * Contains the field set defaults.
     */
    defaults: {
        labelWidth: 170,
        anchor: '100%'
    },

    snippets: {
        save: '{s name=config/save}Save{/s}',
        cancel: '{s name=config/cancel}Cancel{/s}',
        importPicturesLabel: '{s name=config/import/pictures_label}Load product images during first import{/s}',
        importPicturesHelp: '{s name=config/import/pictures_help}The import of images can slow down the import. If you want to import many products, you should not activate and import the pictures on the CronJob.{/s}',
        overwritePropertiesLabel: '{s name=config/import/overwrite_properties}Overwrite the following properties during import{/s}',
        overwritePropertiesHelp: '{s name=config/import/overwrite_properties_help}The fields selected here will automatically be overwritten when the source changes this store. You can define item-level exceptions.{/s}',
        overwriteProductName: '{s name=config/import/overwrite_product_name}Product name{/s}',
        overwriteProductPrice: '{s name=config/import/overwrite_product_price}Price{/s}',
        overwriteProductImages: '{s name=config/import/overwrite_product_images}Image{/s}',
        overwriteProductShortDescription: '{s name=config/import/overwrite_product_short_description}Short description{/s}',
        overwriteProductLongDescription: '{s name=config/import/overwrite_product_long_description}Long description{/s}',
        overwriteProductAdditionalDescription: '{s name=config/import/overwrite_product_additional_description}Connect description{/s}',
        articleImagesLimitImportLabel: '{s name=config/import/pictures_limit_label}Number of products per image import pass{/s}',
        productImportSettingsTitle: '{s name=config/import/product_import_settings_title}Product{/s}',
        productImportImageSettingsTitle: '{s name=config/import/image_settings_title}Product images{/s}',
        overwritePropertiesHelptext: '{s name=config/import/overwrite_properties_helptext}Gebe an, welche Felder überschrieben werden sollen, wenn dein Lieferant sie ändert. Diese Einstellung kannst du auch pro Artikel treffen. Gehe dafür direkt in den Artikel und dann auf den Tab Connect.{/s}'
    },

    initComponent: function() {
        var me = this;

        me.items = me.createElements();
        me.dockedItems = [{
            xtype: 'toolbar',
            dock: 'bottom',
            ui: 'shopware-ui',
            cls: 'shopware-toolbar',
            items: me.getFormButtons()
        }];

        me.importConfigStore = Ext.create('Shopware.apps.Connect.store.config.Import').load({
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
    getFormButtons: function() {
        var me = this,
            buttons = ['->'];

        var saveButton = Ext.create('Ext.button.Button', {
            text: me.snippets.save,
            action:'save-import-config',
            cls:'primary'
        });

        var cancelButton = Ext.create('Ext.button.Button', {
            text: me.snippets.cancel,
            handler: function(btn) {
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

        var categoriesStore = Ext.create('Shopware.apps.Base.store.CategoryTree');
        categoriesStore.load();

        var numStore = Ext.create('Ext.data.Store', {
            fields: ['value'],
            data : [
                { value: 5 },
                { value: 25 },
                { value: 50 },
                { value: 100 },
                { value: 150 }
            ]
        });

        me.imageLimitImportField = Ext.create('Ext.form.field.ComboBox', {
            name: 'articleImagesLimitImport',
            fieldLabel: me.snippets.articleImagesLimitImportLabel,
            labelWidth: me.defaults.labelWidth,
            editable: false,
            valueField: 'value',
            displayField: 'value',
            width: 250,
            store: numStore
        });

        var containerTop = Ext.create('Ext.form.FieldSet', {
            flex: 1,
            title: me.snippets.productImportSettingsTitle,
            layout: 'vbox',
            items: [
                {
                    xtype: 'container',
                    margin: '0 0 20 0',
                    width: 600,
                    html: '<p>' + me.snippets.overwritePropertiesHelptext + '</p>'
                },
                {
                    xtype      : 'fieldcontainer',
                    fieldLabel : me.snippets.overwritePropertiesLabel,
                    defaultType: 'checkboxfield',
                    labelWidth: me.defaults.labelWidth,
                    items: [
                        {
                            boxLabel  : me.snippets.overwriteProductName,
                            name      : 'overwriteProductName',
                            inputValue: 1,
                            helpText: me.snippets.overwritePropertiesHelp,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductPrice,
                            name      : 'overwriteProductPrice',
                            inputValue: 1,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductImages,
                            name      : 'overwriteProductImage',
                            inputValue: 1,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductShortDescription,
                            name      : 'overwriteProductShortDescription',
                            inputValue: 1,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductLongDescription,
                            name      : 'overwriteProductLongDescription',
                            inputValue: 1,
                            uncheckedValue: 0
                        }, {
                            boxLabel  : me.snippets.overwriteProductAdditionalDescription,
                            name      : 'overwriteProductAdditionalDescription',
                            inputValue: 1,
                            uncheckedValue: 0
                        }
                    ]
                }
            ]
        });

        var containerBottom = Ext.create('Ext.form.FieldSet', {
            flex: 1,
            title: me.snippets.productImportImageSettingsTitle,
            layout: 'vbox',
            items: [
                {
                    xtype      : 'fieldcontainer',
                    defaultType: 'checkboxfield',
                    labelWidth: me.defaults.labelWidth,
                    items: [
                        {
                            xtype: 'checkbox',
                            name: 'importImagesOnFirstImport',
                            fieldLabel: me.snippets.importPicturesLabel,
                            helpText: me.snippets.importPicturesHelp,
                            inputValue: 1,
                            uncheckedValue: 0,
                            labelWidth: me.defaults.labelWidth,
                            listeners:{
                                change: function(checkbox, newValue, oldValue, opts){
                                    me.enableImageImportLimit(checkbox);
                                },
                                beforeRender: function(checkbox, opts) {
                                    me.enableImageImportLimit(checkbox);
                                }
                            }
                        }, me.imageLimitImportField
                    ]
                }
            ]
        });

        return [ containerTop, containerBottom ];
    },

    /**
     * Populate import config form
     */
    populateForm: function() {
        var me = this,
            record = me.importConfigStore.getAt(0);

        if (!record) {
            record = Ext.create('Shopware.apps.Connect.model.config.Import');
        }
        me.loadRecord(record);
    },

    /**
     * Enable / disable number of products which are proceed
     * by images cron at the same time.
     * It depends on import images on first import of products
     *
     * @param checkbox
     */
    enableImageImportLimit: function(checkbox) {
        var me = this;

        me.imageLimitImportField.setDisabled(checkbox.getValue());
    }
});
//{/block}

