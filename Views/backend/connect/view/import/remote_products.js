//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/import/remote_products"}
Ext.define('Shopware.apps.Connect.view.import.RemoteProducts', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.connect-products',
    store: 'import.RemoteProducts',

    border: false,

    selModel: new Ext.selection.RowModel({
        mode: "MULTI"
    }),

    viewConfig: {
        plugins: {
            ptype: 'gridviewdragdrop',
            appendOnly: true,
            dragGroup: 'local',
            dropGroup: 'remote'
        }
    },

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            height: 200,
            width: 400,

            dockedItems: [
                me.getPagingToolbar()
            ],
            columns: me.getColumns()
        });

        me.callParent(arguments);

    },

    getColumns: function() {
        return [
            {
                header: 'Aritkel Nr.',
                dataIndex: 'Detail_number',
                flex: 1
            }, {
                header: 'Name',
                dataIndex: 'Article_name',
                flex: 4
            }, {
                header: 'Hersteller',
                dataIndex: 'Supplier_name',
                flex: 3
            }, {
                header: 'Preis (brutto)',
                dataIndex: 'Price_basePrice',
                flex: 3
            }, {
                header: 'Steuersatz',
                dataIndex: 'Tax_name',
                flex: 1
            }
        ];
    },

    getPagingToolbar: function() {
        var me = this;

        var pagingBar = Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true,
            doRefresh : function(){
                var toolbar = this,
                    current = toolbar.store.currentPage;

                if (toolbar.fireEvent('beforechange', toolbar, current) !== false) {
                    toolbar.store.loadPage(current);
                }

                me.fireEvent('reloadRemoteCategories');
            }
        });

        return pagingBar;
    }
});
//{/block}