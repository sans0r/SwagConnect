{block name="frontend_index_header_css_screen" append}
    <link rel="stylesheet" href="{link file='frontend/_resources/styles/bepado.css'}" />
{/block}
{block name="frontend_index_header_javascript" append}
    {if $bepadoProduct || $hasBepadoProduct}
        <script src="{link file='frontend/_resources/javascripts/bepado.js'}"></script>
    {/if}
{/block}

{block name='frontend_checkout_cart_premiums' prepend}
    {include file='frontend/bepado/checkout_cart.tpl'}
{/block}

{block name='frontend_checkout_confirm_premiums' prepend}
    {include file='frontend/bepado/checkout_cart.tpl'}
{/block}

{block name='frontend_checkout_cart_item_image' prepend}
    {if $shopId && $bepadoShopInfo}
        <span class="checkout_item_bepado"><span>&nbsp;</span></span>
    {/if}
{/block}

{block name='frontend_checkout_cart_item' append}
    {if $shopId}
        <div class="bepado-additional-info-checkout">
            <span class="bepado-label label-separate-dispatch">{s name="frontend_checkout_cart_bepado_dispatch"}Separater Versand{/s}</span>
            {if $bepadoShopInfo}
                <span class="bepado-display display-shop-name">Artikel von {$bepadoShops[$shopId]->name}</span>
            {/if}
        </div>
    {/if}
{/block}

{block name='frontend_checkout_error_messages_voucher_error' append}
{* Voucher error *}
{if $phoneMissing}
    {include 'frontend/bepado/phone_message.tpl'}
{/if}
{/block}

{*
    Show message during checkout, if product price / availability has changed
*}
{block name='frontend_checkout_cart_cart_head' append}
	{if $bepadoMessages[$shopId]}
        <div class="doublespace"></div>
		<div class="error" style="margin:0">
			{foreach from=$bepadoMessages[$shopId] item=bepadomessage}
				{$message = $bepadomessage->message}
				{foreach from=$bepadomessage->values key=key item=value}
					{$message = "%{$key}"|str_replace:$value:$message}
				{/foreach}
				{$message}<br>
			{/foreach}
            <br>
            <a href="{url}">{s name="frontend_checkout_cart_bepado_refresh"}Klicken Sie hier um die Seite zu aktualisieren{/s}</a>
		</div>
        <div class="space"></div>
	{/if}

    {$smarty.block.parent}

	{if $bepadoShops}
    	{include file='frontend/bepado/shop_header.tpl' hideSinglePrice=false}
	{/if}
{/block}


{block name='frontend_checkout_confirm_item'}
    {assign var="lastProduct" value=$sBasket.content|@end}

    {if counter eq 0 && $bepadoContent}
        {include file='frontend/bepado/shop_header.tpl' hideSinglePrice=true}
    {/if}

    {if (!$sBasketItem.bepadoShopId || !$bepadoContent) && !$shopId}
        {$smarty.block.parent}

        {if $lastProduct.id eq $sBasketItem.id}
            <div class="border-top">
            </div>
        {/if}
    {/if}

    {if $shopId}
        {include file='frontend/checkout/cart_item.tpl'}
        <div class="bepado-additional-info-checkout">
            <span class="bepado-label label-separate-dispatch">{s name="frontend_checkout_cart_bepado_dispatch"}Separater Versand{/s}</span>
            {if $bepadoShopInfo}
                <span class="bepado-display display-shop-name">Artikel von {$bepadoShops[$shopId]->name}</span>
            {/if}
        </div>
    {/if}

    {if $lastProduct.id eq $sBasketItem.id}
        {include file='frontend/bepado/checkout_cart.tpl'}
    {/if}

{/block}



{*
    Hide "buy" button on checkout finish if messages where passed.
*}
{block name='frontend_checkout_confirm_submit' prepend}
    {if $bepadoMessages}
        {include 'frontend/bepado/finish_message.tpl'}
    {elseif $phoneMissing}
        {include 'frontend/bepado/phone_message.tpl'}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
{block name='frontend_checkout_confirm_stockinfo'}
    {if $bepadoMessages}
        {include 'frontend/bepado/finish_message.tpl'}
    {elseif $phoneMissing}
        {include 'frontend/bepado/phone_message.tpl'}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}