//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/export/stream/list"}
Ext.define('Shopware.apps.Connect.view.export.stream.List', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.connect-export-stream-list',

    border: false,

    store: 'export.StreamList',

    selModel: Ext.create('Shopware.apps.Connect.view.export.stream.CustomCheckboxModel', {
        selectedAll: false, // you can add whatever normal configuration properties you want here
        mode: 'MULTI',
        listeners: {
            selectall: function (scope) {
                if (!scope.selectedAll) {
                    scope.selectedAll = true;
                } else {
                    scope.deselectAll();
                    scope.selectedAll = false;
                }
            }
        }
    }),

    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            dockedItems: [
                me.getPagingToolbar()
            ],
            features: [me.getGrouping()],
            columns: me.getColumns()
        });

        me.callParent(arguments);
    },
    listeners: {
        beforeselect: function (sm, record) {
            if (record.get('enableRow') == false ) return false;
        },

        selectionchange: function (sm, selected) {
            // deselect disabled records
            if (selected.length > 0) {
                Ext.Array.each(selected, function (record) {
                    if (record.get('enableRow') == false) {
                        sm.deselect(record, true);
                    }
                });
            }
        }
    },

    getColumns: function() {
        var me = this;

        return [{
            header: '{s name=export/columns/name}Name{/s}',
            dataIndex: 'name',
            flex: 4,
            renderer: function(value, metaData, record) {
                if (record.get('enableRow') == false) {
                    return '<div class="sc-transparency-color">' + value + '</div>'
                }
                return value;
            }
        }, {
            header: '{s name=export/columns/product_amount}Product amount{/s}',
            dataIndex: 'productCount',
            flex: 1
        }, {
            header: '{s name=export/columns/status}Status{/s}',
            dataIndex: 'exportStatus',
            flex: 1,
            renderer: function(value, metaData, record) {
                var className = '',
                    label;

                if (!value) {
                    return;
                }

                if (me.iconMapping.hasOwnProperty(value)) {
                    className = me.iconMapping[value];
                }

                if (record.get('enableRow') == false) {
                    className += ' sc-transparency';
                }

                if(record.get('exportMessage')) {
                    metaData.tdAttr = 'data-qtip="' +  record.get('exportMessage') + '"';
                } else {
                    label = (value in me.iconLabelMapping) ? me.iconLabelMapping[value] : value;

                    metaData.tdAttr = 'data-qtip="' +  label + '"';
                }

                return '<div class="' + className + '" style="width: 16px; height: 16px;"></div>';
            }
        }];
    },

    getGrouping: function() {
        return Ext.create('Ext.grid.feature.Grouping', {
            groupHeaderTpl: [
                '{literal}{name:this.formatName}{/literal}',
                {
                    formatName: function(type) {
                        if (type == 2) {
                            return '{s name=export/selection_streams}Selection streams{/s}';
                        } else {
                            return '{s name=export/condition_streams}Condition streams{/s}';
                        }
                    }
                }
            ],
            hideGroupedHeader: true,
            startCollapsed: false
        });
    },

    /**
     * Creates a paging toolbar with additional page size selector
     *
     * @returns Array
     */
    getPagingToolbar: function() {
        var me = this;
        var pageSize = Ext.create('Ext.form.field.ComboBox', {
            labelWidth: 120,
            cls: Ext.baseCSSPrefix + 'page-size',
            queryMode: 'local',
            width: 180,
            listeners: {
                scope: me,
                select: function(combo, records) {
                    var record = records[0],
                        me = this;

                    me.store.pageSize = record.get('value');
                    me.store.loadPage(1);
                }
            },
            store: Ext.create('Ext.data.Store', {
                fields: [ 'value' ],
                data: [
                    { value: '20' },
                    { value: '40' },
                    { value: '60' },
                    { value: '80' },
                    { value: '100' }
                ]
            }),
            displayField: 'value',
            valueField: 'value',
            editable: false,
            emptyText: '20'
        });
        pageSize.setValue(me.store.pageSize);

        var pagingBar = Ext.create('Ext.toolbar.Paging', {
            store: me.store,
            dock:'bottom',
            displayInfo:true
        });

        pagingBar.insert(pagingBar.items.length - 2, [ { xtype: 'tbspacer', width: 6 }, pageSize ]);
        return pagingBar;
    }
});
//{/block}