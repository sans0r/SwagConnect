//{namespace name=backend/connect/view/main}

//{block name="backend/connect/view/register/panel"}
Ext.define('Shopware.apps.Connect.view.register.panel', {
    extend: 'Ext.container.Container',
    alias: 'register.panel',

    /**
     * Contains all snippets for the view component
     * @object
     */
    snippets: {
        title: '{s name=account/title}Shopware ID{/s}',
        descriptionMessage: '{s name=account/description_message}Here you can create you personal Shopware ID. The Shopware ID will give you access to your Shopware account in our forum, wiki and other community resources. It will also grant you access to our plugin store, where you can find many more plugins that will help you easily customize your shop to your needs.{/s}'
    },

    cls: 'plugin-manager-login-window',
    header: false,
    padding: 40,
    modal: true,

    initComponent: function () {
        var me = this;

        Ext.applyIf(me, {
            items : [
                me.createHeadline(),
                me.createDescriptionText(),
                me.createLayouts()
            ]
        });
        me.callParent(arguments);
    },

    createLayouts: function () {
        var me = this;

        return {
            border: false,
            layout: {
                type: 'hbox',
                align: 'stretch'
            },
            anchor: '100%',
            items: [
                me.createLoginPanel(),
                me.createRegisterPanel()
            ]
        };

    },

    createHeadline: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            border: false,
            layout: 'hbox',
            anchor: '100%',
            cls: 'headline-container',
            items: [
                Ext.create('Ext.Component', {
                    html: me.snippets.title,
                    width: 680,
                    cls: 'headline'
                })
            ]
        });
    },

    createDescriptionText: function() {
        var me = this;
        return {
            html: me.snippets.descriptionMessage,
            margin: '0 0 40 0',
            cls: 'description-text',
            width: 720,
            border: false
        }
    },

    createLoginPanel: function () {
        var me = this;

        return Ext.create('Shopware.apps.Connect.view.register.loginPanel', {
            callback: me.callback,
            margin: '0 25 0 0'
        });
    },

    createRegisterPanel: function () {
        var me = this;

        return Ext.create('Shopware.apps.Connect.view.register.registerPanel', {
            cls: 'plugin-manager-login-window plugin-manager-register-form',
            callback: me.callback,
            margin: '0 0 0 15'
        });
    }


});
//{/block}