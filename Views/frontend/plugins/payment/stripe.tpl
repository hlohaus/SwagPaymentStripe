{$stripeCustomerId = $sUserData.additional.user.viisonStripeCustomerId}
<div class="debit stripe" data-stripe-payment="true" data-publishableKey="{config name=stripePublishableKey}">

{if $stripeCustomerId}
    <div class="stripe-customer">
        {$card = $stripeSources[0]}
        <input type="checkbox" class="checkbox" checked="checked" value="1"
               id="stripeUseAccount" name="stripeUseAccount">
        <label class="has-account--label" for="stripeUseAccount">{s name=form/reuse_card}{/s}</label>
        <span class="card--info">{s name=form/saved_card force}({$card.brand} endet auf {$card.last4}){/s}</span>
    </div>
{/if}
    <div class="stripe-panel">
        <div class="stripe-number">
            <input type="text" size="20"
                   placeholder="{s name=form/card/number}{/s}"
                   class="cc-number" data-stripe="number"
                   autocomplete="cc-number"
                   required="required"
                   aria-required="true">
            <span>{s name=form/card/info}{/s}</span>
        </div>
        <div class="stripe-cvc">
            <input type="text" size="4"
                   placeholder="{s name=form/card/cvc}{/s}"
                   required="required" aria-required="true"
                   autocomplete="off" data-stripe="cvc">
        </div>
        <div class="stripe-expiration">
            <label class="expiration--label" for="">{s name=form/card/expiry}{/s}</label>
            <input type="number" size="2"
                   class="exp--input"
                   placeholder="{s name=form/card/exp-month}{/s}"
                   required="required" aria-required="true"
                   data-stripe="exp-month">
            <span> / </span>
            <input type="number" size="4"
                   placeholder="{s name=form/card/exp-year}{/s}"
                   required="required" aria-required="true"
                   class="exp--input"
                   data-stripe="exp-year">
        </div>
        {if !$sUserData.additional.user.accountmode}
        <div class="stripe--create-account">
            <input type="checkbox" class="checkbox" checked="checked" value="1" id="stripeCreateAccount" name="stripeCreateAccount">
            <label class="create-account--label" for="stripeCreateAccount">{s name=form/save_card}{/s}</label>
        </div>
        {/if}
    </div>
</div>
