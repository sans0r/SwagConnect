<?php

namespace ShopwarePlugins\Connect\Subscribers;

/**
 * Extends the dispatch module and removes non-connect aware dispatches, if connect products are in the basket
 *
 * Class Dispatches
 * @package ShopwarePlugins\Connect\Components\Subscribers
 */
class Dispatches extends BaseSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Shipping' => 'onPostDispatchBackendShipping',
            'sAdmin::sGetPremiumDispatches::after' => 'onFilterDispatches',
        );
    }

    /**
     * If connect products are in the basket: Remove dispatches which are not allowed for connect
     *
     * @event sAdmin::sGetPremiumDispatches::after
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onFilterDispatches(\Enlight_Hook_HookArgs $args)
    {
        $dispatches = $args->getReturn();

        if (!count($dispatches) > 0) {
            return;
        }

        // If no connect products are in basket, don't modify anything
        if (!$this->getHelper()->hasBasketConnectProducts(Shopware()->SessionID())) {
            return;
        }

        $dispatchIds = array_keys($dispatches);

        $questionMarks = implode(', ', str_split(str_repeat('?', count($dispatchIds))));

        $sql = "
        SELECT `dispatchID`
        FROM s_premium_dispatch_attributes
        WHERE `connect_allowed` > 0
        AND `dispatchID` IN ({$questionMarks})
        ";
        
        $allowedDispatchIds = Shopware()->Db()->fetchCol($sql, $dispatchIds);

        // Remove the non-allowed dispatches from dispatch array
        foreach ($dispatches as $id => $dispatch) {
            if (!in_array($id, $allowedDispatchIds)) {
                unset($dispatches[$id]);
            }
        }

        $args->setReturn($dispatches);
    }

    /**
     * Extends the shipping backend module
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendShipping(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch($request->getActionName()) {
            case 'load':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();
                // The version is needed as in older sw-versions the attribute cannot be extended easily
                if (\Shopware::VERSION != '__VERSION__' && version_compare(\Shopware::VERSION, '4.2.0', '<')) {
                    $subject->View()->assign('useOldConnectShippingAttributeExtension', true);
                }

                $subject->View()->extendsTemplate(
                    'backend/shipping/connect.js'
                );

                break;
            default:
                break;
        }
    }


}