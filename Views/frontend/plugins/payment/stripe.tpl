{$stripeCustomerId = $sUserData.additional.user.viisonStripeCustomerId}
<div class="debit stripe" data-stripe-payment="true" data-publishableKey="{config name=stripePublishableKey}">

{if $stripeCustomerId}
    <div class="stripe-customer">
        <input type="checkbox" class="checkbox" checked="checked" value="1"
               id="stripeUseAccount" name="stripeUseAccount">
        <label class="has-account--label" for="stripeUseAccount">Hinterlegte Kreditkarte verwenden</label>
    </div>
{/if}
    <div class="stripe-panel">
        <div class="stripe-number">
            <input type="text" size="20"
                   placeholder="Kreditkartennummer*"
                   class="cc-number" data-stripe="number"
                   autocomplete="cc-number"
                   required="required"
                   aria-required="true">
            <span>Enter the number without spaces or hyphens.</span>
        </div>
        <div class="stripe-cvc">
            <input type="text" size="4"
                   placeholder="Kartenprüfnummer*"
                   required="required" aria-required="true"
                   autocomplete="off" data-stripe="cvc">
        </div>
        <div class="stripe-expiration">
            <label class="expiration--label" for="">Gültig bis*</label>
            <input type="number" size="2"
                   class="exp--input"
                   placeholder="MM"
                   required="required" aria-required="true"
                   data-stripe="exp-month">
            <span> / </span>
            <input type="number" size="4"
                   placeholder="YY"
                   required="required" aria-required="true"
                   class="exp--input"
                   data-stripe="exp-year">
        </div>
        {if !$sUserData.additional.user.accountmode}
        <div class="stripe--create-account">
            <input type="checkbox" class="checkbox" checked="checked" value="1" id="stripeCreateAccount" name="stripeCreateAccount">
            <label class="create-account--label" for="stripeCreateAccount">Kreditkarte im Kundenkonto speichern</label>
        </div>
        {/if}
    </div>
</div>

