<link rel="stylesheet" href="/modules/paynl_paymentmethods/paynl.css" />
<script type="text/javascript" src="/modules/paynl_paymentmethods/paynl.js"></script>

{foreach from=$profiles key=k item=v}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module" >
                {if $v.id == 10 && $showbanks}                    
                    <a data-ajax="false" class="paynl_paymentmethod showbanks" >
                        <img src="https://static.pay.nl/payment_profiles/75x75/{$v.id}.png" alt="{$v.name}"  />
                        {$v.name}{if isset($v.extraCosts)}  <span class="">{$v.extraCosts}</span> {/if}
                    </a>
                    <span id="showbanks_submenu_container">
                        <span id="showbanks_submenu">
                            {foreach from=$banks key=bk item=b}
                                <a data-ajax="false" class="paynl_paymentmethod bank" href="{$link->getModuleLink('paynl_paymentmethods', 'payment', [pid => {$v.id}, bankid => {$b.id}], true)|escape:'html'}" title="{l s=$b.name mod='paynl_paymentmethods'}">
                                    <img src="https://static.pay.nl/ideal/banks/25x25/{$b.id}.png" alt="{$b.name}"  />
                                    {$b.name}
                                </a>
                            {/foreach}
                        </span>
                    </span>
                {else}
                    <a data-ajax="false" class="paynl_paymentmethod " href="{$link->getModuleLink('paynl_paymentmethods', 'payment', [pid => {$v.id}], true)|escape:'html'}" title="{l s=$v.name mod='paynl_paymentmethods'}">
                        <img src="https://static.pay.nl/payment_profiles/75x75/{$v.id}.png" alt="{$v.name}"  />
                        {$v.name}{if isset($v.extraCosts)}  <span class="">{$v.extraCosts}</span> {/if}
                    </a>
                {/if}
            </p>
        </div>
    </div>
{/foreach}
