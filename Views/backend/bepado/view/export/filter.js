//{namespace name=backend/bepado/view/main}

//{block name="backend/bepado/view/export/filter"}
Ext.define('Shopware.apps.Bepado.view.export.Filter', {
    extend: 'Ext.container.Container',
    alias: 'widget.bepado-export-filter',

    width: 200,
    layout: {
        type: 'vbox',
        align : 'stretch',
        pack  : 'start'
    },
    //layout: {
    //    type: 'accordion',
    //    animate: Ext.isChrome
    //},
    animCollapse: Ext.isChrome,
    border: false,

    initComponent: function() {
        var me = this;

        me.statusFilter = me.getStatusFilter();
        me.categoryFilter = me.getCategoryFilter();
        me.supplierFilter = me.getSupplierFilter();
        me.searchFilter = me.getSearchFilter();

        Ext.applyIf(me, {
            items: [
                me.statusFilter, me.searchFilter,
                me.supplierFilter, me.categoryFilter
            ]
        });

        me.callParent(arguments);
    },

    getCategoryFilter: function() {
        return {
            xtype: 'treepanel',
            title: '{s name=export/filter/category_title}Category filter{/s}',
            rootVisible: false,
            root: {
                id: 1,
                expanded: true
            },
            store: 'base.CategoryTree',
            flex: 2
        }
    },

    getStatusFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=export/filter/status_title}Status filter{/s}',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype: 'base-element-select',
                name: 'exportStatus',
                anchor: '100%',
                allowBlank: true,
                store: [
                    ['online', 'Online'],
                    ['error', 'Error'],
                    ['insert', 'Inserting'],
                    ['update', 'Updating']
                ]
            }]
        }
    },

    getSupplierFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=export/filter/supplier_title}Supplier filter{/s}',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype: 'base-element-select',
                name: 'supplierId',
                anchor: '100%',
                allowBlank: true,
                store: 'base.Supplier'
            }]
        }
    },

    getSearchFilter: function() {
        return {
            xtype: 'form',
            title: '{s name=export/filter/search_title}Search{/s}',
            height: 65,
            bodyPadding: 5,
            items: [{
                xtype:'textfield',
                name:'searchfield',
                anchor: '100%',
                cls:'searchfield',
                emptyText:'{s name=export/filter/search_empty}Search...{/s}',
                enableKeyEvents:true,
                checkChangeBuffer:500
            }]
        }
    }
});
//{/block}