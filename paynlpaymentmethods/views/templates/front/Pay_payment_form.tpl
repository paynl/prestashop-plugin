<form action="{$action}" method="POST" id="payment-form" class="paynl">
    <input type="hidden" name="payment_option_id" value="{$payment_option_id}"/>
    {if !empty($payment_options)}
        <div class="form-group row PaynlBanks {$logoClass} {$type}">
            {if $type == 'dropdown'}
                <fieldset>
                    <legend>{$payment_dropdown_text}</legend>
                    <select class="form-control form-control-select" name="{$payment_option_name}">
                        <option value="">{$payment_option_text}</option>
                        {foreach from=$payment_options item=_option}
                            <option value="{$_option['id']}">{$_option['name']}</option>
                        {/foreach}
                    </select>
                </fieldset>
            {elseif $type == 'radio'}
                <ul class="pay_radio_select">
                    {foreach from=$payment_options item=_option}
                    <li>
                        <label>
                            <input type="radio" name="{$payment_option_name}" value="{$_option['id']}">
                            {if $logoClass != 'noLogo'}
                                <img src="/modules/paynlpaymentmethods/views/images/issuers/qr-{$_option['id']}.png" loading="lazy">
                            {/if}
                            <span>{$_option['name']}</span>
                        </label>
                        {/foreach}
                </ul>
            {/if}
            {if !empty($payment_location)}
                <fieldset>
                    <legend>Payment</legend>
                    <select class="form-control form-control-select" name="pinMoment">
                        <option value="direct">Pay by card</option>
                        <option value="backorder">Pay later, at pickup</option>
                    </select>
                </fieldset>
            {/if}
        </div>
    {/if}
    {if !empty($description)}
        <div class="paynl_payment_description">
            {{$description}}
        </div>
    {/if}
</form>