{extends file="parent:frontend/account/payment.tpl"}

{block name="frontend_index_header_javascript" append}
    {include file="frontend/plugins/payment/stripe_header.tpl"}
{/block}
