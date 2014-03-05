//{block name="backend/bepado/store/mapping/export"}
Ext.define('Shopware.apps.Bepado.store.mapping.Export', {
    extend : 'Ext.data.TreeStore',

    autoLoad: false,
    model: 'Shopware.apps.Bepado.model.main.Mapping',
    root: {
        id: 1,
        expanded: true
    },
    proxy : {
        type : 'ajax',
        api : {
            read : '{url action=getExportMappingList}',
            update: '{url action=setExportMappingList targetField=rows}'
        },
        reader : {
            type : 'json',
            root: 'data'
        }
    },
    constructor: function(config) {
        config.root = Ext.clone(this.root);
        this.callParent([config]);
    }
});
//{/block}