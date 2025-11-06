<div class="panel" id="fieldset_1">
    <div class="panel-heading">
        <i class="icon-euro"></i> {l s='Payment methods' mod='paynlpaymentmethods'}
    </div>
     <ul id="sortable_paymentmethods" class="list-group">
        {foreach from=$paymentmethods item=paymentmethod}
            <li href="#" class="list-group-item row paynl_payment_method paynl_payment_method_id_{$paymentmethod->id}">    
                <form>   
                    <input type="hidden" value="{$paymentmethod->id}" name="id" />   
                    <input type="hidden" value="{$paymentmethod->brand_id}" name="brand_id" />        
                    <div class="row">
                        <span class="col-xs-1">
                            <div class="sortHandle">
                            <i class="icon-reorder"></i>
                            </div>
                        </span>
                        <span class="col-xs-1">   
                            <span class="paynl_switch enabledSwitch green switch {if $paymentmethod->enabled}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->enabled}" name="enabled" {if $paymentmethod->enabled}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>                     
                        </span>
                        <span class="col-xs-1 clickable openPaymentDetails checkfew">
                            <img width="50" src="{$image_url}{$paymentmethod->image_path}">
                            <input type="hidden" name="image_path" value="{$paymentmethod->image_path}"/>
                        </span>
                        <span class="col-xs-9 clickable openPaymentDetails">
                            <h4 class="list-group-item-heading">{$paymentmethod->name}</h4>
                            <p class="list-group-item-text">{$paymentmethod->description}</p>
                            <span class="pull-right">
                                <i class="icon-chevron-up icon-chevron-down"></i>
                            </span>
                        </span>
                    </div>
                    <div class="row paymentdetails hidden">
                        <div class="formHorizontal">
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s='Name' mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <input type="text" value="{$paymentmethod->name}" name="name">
                                    <p class="help-block">
                                        {l s='The name of the payment method' mod='paynlpaymentmethods'}
                                    </p>
                                    {if isset($languages) && count($languages) > 0}
                                        <div class="translations">
                                        <div class="show_translations">{l s='Translations' mod='paynlpaymentmethods'} &nbsp; <i class="icon-chevron-down"></i></div>                            
                                            <br/>
                                            <div class="language-options hidden">                                                          
                                                    {foreach from=$languages item=language}
                                                        <label class="control-label col-lg-2 align-left" style="padding:0;">{$language.name}</label>
                                                        <div class="col-lg-10">
                                                            {$key="name_{$language.iso_code}"}
                                                            <input type="text" value="{$paymentmethod->$key}" name="{$key}">            
                                                        </div>                          
                                                    {/foreach}                                                
                                                <div style="clear:both"></div>                                
                                            <br/>
                                        </div>
                                    </div>
                                    {/if}
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s='Description' mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <input type="text" value="{$paymentmethod->description}" name="description">                             
                                    <p class="help-block">
                                        {l s='Short description for the paymentmethod, Will be shown on selection of the payment method' mod='paynlpaymentmethods'}
                                    </p>
                                    {if isset($languages) && count($languages) > 0}
                                        <div class="translations">
                                        <div class="show_translations">{l s='Translations' mod='paynlpaymentmethods'} &nbsp; <i class="icon-chevron-down"></i></div>
                                            <br/>
                                            <div class="language-options hidden">                            
                                                    {foreach from=$languages item=language}
                                                        <label class="control-label col-lg-2 align-left" style="padding:0;">{$language.name}</label>
                                                        <div class="col-lg-10">
                                                            {$key="description_{$language.iso_code}"}
                                                            <input type="text" value="{$paymentmethod->$key}" name="{$key}">          
                                                        </div>                          
                                                    {/foreach}                                                
                                                <div style="clear:both"></div>                                
                                            <br/>
                                        </div>
                                    </div>
                                    {/if}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s='Minimum amount' mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <input style="width:150px;" type="number" value="{$paymentmethod->min_amount}" name="min_amount">              
                                    <p class="help-block">
                                        {l s='The minimum amount for this payment method' mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s='Maximum amount' mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">        
                                    <input style="width:150px;" type="number" value="{$paymentmethod->max_amount}" name="max_amount">  
                                    <p class="help-block">
                                        {l s='The maximum amount for this payment method' mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s="Limit countries" mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->limit_countries}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->limit_countries}" name="limit_countries" {if $paymentmethod->limit_countries}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>                 
                                    <p class="help-block">
                                        {l s="Enable this if you want to limit this payment method for certain countries" mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group limit_countries_required {if !$paymentmethod->limit_countries}hidden{/if}">
                                <label class="control-label col-lg-3 align-right">{l s="Allowed countries" mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <select class="select-multiple" name="allowed_countries" multiple style="width: 100%">
                                        {foreach from=$available_countries item=country}
                                            <option value="{$country.id_country}" {if in_array($country.id_country, $paymentmethod->allowed_countries)}selected="selected"{/if}>{$country.name}</option>                        
                                        {/foreach}
                                    </select>
                                    <p class="help-block">
                                        {l s="Select all countries where this paymentmethod may be used" mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s="Limit carriers" mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->limit_carriers}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->limit_carriers}" name="limit_carriers" {if $paymentmethod->limit_carriers}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>
                                    <p class="help-block">
                                        {l s="Enable this if you want to limit this payment method for certain carriers" mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group limit_carriers_required {if !$paymentmethod->limit_carriers}hidden{/if}">
                                <label class="control-label col-lg-3 align-right">{l s="Allowed carriers" mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <select class="select-multiple" name="allowed_carriers" multiple style="width: 100%">
                                        {foreach from=$available_carriers item=carrier}
                                            <option value="{$carrier.id_carrier}" {if in_array($carrier.id_carrier, $paymentmethod->allowed_carriers)}selected="selected"{/if}>{$carrier.name}</option>                        
                                        {/foreach}
                                    </select>
                                    <p class="help-block">
                                        {l s="Select all carriers where this paymentmethod may be used" mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s='Use fee as percentage' mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <div class="btn-group">
                                    <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->fee_percentage}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->fee_percentage}" name="fee_percentage" {if $paymentmethod->fee_percentage}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>
                                    </div>
                                    <p class="help-block">
                                        {l s='The type of payment fee for this payment method' mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s='Value of the fee' mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">
                                    <input style="width:150px;" type="number" value="{$paymentmethod->fee_value}" name="fee_value">  
                                    <p class="help-block">
                                        {l s='Value of the fee (including TAX). Tax will be applied according to the paymentfee-product tax-configuration.' mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-lg-3 align-right">{l s='Customer type' mod='paynlpaymentmethods'}</label>
                                <div class="col-lg-9">                                    
                                    <select name="customer_type">
                                        <option value="both" {if $paymentmethod->customer_type == 'both'}selected{/if}>Show for both</option>
                                        <option value="private" {if $paymentmethod->customer_type == 'private'}selected{/if}>Private only</option>
                                        <option value="business" {if $paymentmethod->customer_type == 'business'}selected{/if}>Businesses only</option>
                                    </select>                                    
                                    <p class="help-block">
                                        {l s='Customer type' mod='paynlpaymentmethods'}
                                    </p>
                                </div>
                            </div>

                            {if in_array($paymentmethod->id, $showFastcheckoutOptionsIdeal)}                                
                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right" style="font-size: 25px; margin-top: 25px;">{l s='Fastcheckout' mod='paynlpaymentmethods'}</label>
                                    <div class="col-lg-9" style="height:80px;">                                       
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right">{l s="Cart page" mod='paynlpaymentmethods'}<div class="tooltipPAY tooltipPAYsettings tooltipPAYdropdown">?<span class="tooltipPAYtext">{l s="Show the fast checkout button on the cart page. This button allows users to checkout directly from the cart without the need to fill in their address." mod='paynlpaymentmethods'}</span></div></label>
                                    <div class="col-lg-9">
                                        <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->fastcheckout_cart_page}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->fastcheckout_cart_page}" name="fastcheckout_cart_page" {if $paymentmethod->fastcheckout_cart_page}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>                 
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right">{l s="Minicart" mod='paynlpaymentmethods'}<div class="tooltipPAY tooltipPAYsettings tooltipPAYdropdown">?<span class="tooltipPAYtext">{l s="Show the fast checkout button on the minicart. This button allows users to checkout directly from the cart without the need to fill in their address." mod='paynlpaymentmethods'}</span></div></label>
                                    <div class="col-lg-9">
                                        <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->fastcheckout_minicart}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->fastcheckout_minicart}" name="fastcheckout_minicart" {if $paymentmethod->fastcheckout_minicart}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>                 
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right">{l s="Product page" mod='paynlpaymentmethods'}<div class="tooltipPAY tooltipPAYsettings tooltipPAYdropdown">?<span class="tooltipPAYtext">{l s="Show the fast checkout button on every product page. This button allows users to checkout directly from the cart without the need to fill in their address." mod='paynlpaymentmethods'}</span></div></label>
                                    <div class="col-lg-9">
                                        <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->fastcheckout_product_page}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->fastcheckout_product_page}" name="fastcheckout_product_page" {if $paymentmethod->fastcheckout_product_page}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>                 
                                        <p class="help-block">
                                            {l s='Show the fast checkout button on the enabled pages.' mod='paynlpaymentmethods'}
                                        </p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right">{l s="Guest checkout only" mod='paynlpaymentmethods'}<div class="tooltipPAY tooltipPAYsettings tooltipPAYdropdown">?<span class="tooltipPAYtext">{l s="When enabled, the fast checkout button will only be shown for guest users." mod='paynlpaymentmethods'}</span></div></label>
                                    <div class="col-lg-9">
                                        <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->fastcheckout_guest_only}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->fastcheckout_guest_only}" name="fastcheckout_guest_only" {if $paymentmethod->fastcheckout_guest_only}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>                 
                                        <p class="help-block">
                                            {l s='Show the fast checkout button on the cart page, only for guest customers.' mod='paynlpaymentmethods'}
                                        </p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right">{l s="Show modal" mod='paynlpaymentmethods'}<div class="tooltipPAY tooltipPAYsettings tooltipPAYdropdown">?<span class="tooltipPAYtext">{l s="When enabled, a modal explaining on how fast checkout works will show before going through with fast checkout." mod='paynlpaymentmethods'}</span></div></label>
                                    <div class="col-lg-9">
                                        <span class="paynl_switch enabledSwitch blue switch {if $paymentmethod->fastcheckout_modal}checked{/if}"><small></small><input type=checkbox value="{$paymentmethod->fastcheckout_modal}" name="fastcheckout_modal" {if $paymentmethod->fastcheckout_modal}checked="checked"{/if} style="display:none;"/><span class="switch-text"> </span></span>                 
                                        <p class="help-block">
                                            {l s='Open modal before fast checkout.' mod='paynlpaymentmethods'}
                                        </p>
                                    </div>
                                </div>
                            {/if}

                            {if in_array($paymentmethod->id, $showExternalLogoList)}
                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right">{l s='External logo' mod='paynlpaymentmethods'}</label>
                                    <div class="col-lg-9">
                                        <input type="text" value="{$paymentmethod->external_logo}" name="external_logo">
                                        <p class="help-block">
                                            {l s='URL to a different logo to be used in the checkout' mod='paynlpaymentmethods'}
                                        </p>
                                    </div>
                                </div>
                            {/if}
                            {if isset($showCreateOrderOnList) && is_array($showCreateOrderOnList) && in_array($paymentmethod->id, $showCreateOrderOnList)}
                                <div class="form-group">
                                    <label class="control-label col-lg-3 align-right">{l s='Order Create' mod='paynlpaymentmethods'}</label>
                                    <div class="col-lg-9">                                    
                                        <select name="create_order_on">
                                            <option value="success" {if $paymentmethod->create_order_on == 'success'}selected{/if}>{l s='Create order on success (default)' mod='paynlpaymentmethods'}</option>
                                            <option value="start" {if $paymentmethod->create_order_on == 'start'}selected{/if}>{l s='Create order on payment start' mod='paynlpaymentmethods'}</option>
                                        </select>                                    
                                        <p class="help-block">
                                            {l s='For more information, see our' mod='paynlpaymentmethods'}
                                            <a href="https://developer.pay.nl/docs/prestashop" target="_blank">{l s=' documentation' mod='paynlpaymentmethods'}</a>
                                            .
                                        </p>
                                    </div>
                                </div>
                            {/if}
                        </div>
                    </div>
                </form>
            </li>
        {/foreach}
    </ul>
    <div class="panel-footer">
        <button onclick="submitPaynlForm()" type="submit" value="1" id="module_form_submit_btn2" name="btnSubmit"
                class="btn btn-default pull-right">
            <i class="process-icon-save"></i> {l s='Save'}
        </button>
    </div>
</div>
<div id="test"></div>

<script type="text/javascript">  

    function objectifyForm($formArray) {
        //serialize data function
        var $returnArray = {};
        for (var i = 0; i < $formArray.length; i++){
            $returnArray[$formArray[i]['name']] = $formArray[i]['value'];        
        }
        return $returnArray;
    }

    $('.select-multiple').select2();

    function paynlFormData(){       
        var $paymentmethods = [];
        $('.paynl_payment_method').each(function(){     
            if(this){
                //serialize the form
                var $paymentmethod = $(this).find('form').serializeArray();
                $paymentmethod = objectifyForm($paymentmethod);    

                //Add all checkbox as false or true                      
                $(this).find('input[type="checkbox"]').each(function(){
                   $paymentmethod[$(this).attr("name")] = $(this).is(':checked');                   
                });

                //Add all select data
                $(this).find('select').each(function(){
                    $paymentmethod[$(this).attr("name")] = $(this).find("option:selected").val();
                    if ($(this).prop('multiple')) {
                        var values = new Array();
                        $(this).find("option:selected").each(function() {
                            values.push(this.value);
                        });
                        $paymentmethod[$(this).attr("name")] = values;
                    }    
                });
                $paymentmethods.push($paymentmethod);              
            }            
        }); 
        return $paymentmethods;
    }

    function updatePaynlForm() {
        //Get the updated data and assign it to the PAYNL_PAYMENTMETHODS field
        var $updatedata = paynlFormData();
        if ($updatedata) {
            $('#PAYNL_PAYMENTMETHODS').val(JSON.stringify($updatedata));
        }                      
    }

    function submitPaynlForm(){
        updatePaynlForm();
        var $form = document.getElementById('module_form');
        $form.submit();
        return false;
    }

    $('.openPaymentDetails').click(function(){
        var $details = $(this).parent().parent().find('.paymentdetails');
        var $icon = $(this).parent().parent().find('.pull-right').find('i');
        if($details.hasClass('hidden')){
            $('.paynl_payment_method .paymentdetails').addClass('hidden');
            $('.paynl_payment_method .pull-right i').addClass('icon-chevron-down');
            $('.paynl_payment_method .pull-right i').removeClass('icon-chevron-up');
            $details.removeClass('hidden');
            $icon.removeClass('icon-chevron-down');
            $icon.addClass('icon-chevron-up');
            $("html, body").animate({ scrollTop: $(this).parent().offset().top - 150 }, 500);
        }
        else{
            $details.addClass('hidden');
            $icon.addClass('icon-chevron-down');
            $icon.removeClass('icon-chevron-up');
        }
    });

    $("#sortable_paymentmethods").sortable({ handle: '.sortHandle', update: function( ) {
        updatePaynlForm();
    }});

    $("#sortable_paymentmethods").find('input').keyup(function() {
        updatePaynlForm();
    });

    $("#sortable_paymentmethods").find('select').on('change', function() {
        updatePaynlForm();
    });

    $('.paynl_switch').each(function(){
        $(this).click(function(){
            var $button = $(this);
            if($button.find('input').is(':checked')){
                $button.removeClass('checked');
                $button.find('input').prop('checked', false);    
                $button.parent().parent().parent().find('.'+$button.find('input').attr('name') + '_required').addClass('hidden');                    
            }
            else{
                $button.addClass('checked');
                $button.find('input').prop('checked', true);  
                $button.parent().parent().parent().find('.'+$button.find('input').attr('name') + '_required').removeClass('hidden');     
            }
            updatePaynlForm();            
        });
    });

    $('.show_translations').each(function(){
        $(this).click(function(){
            var $translations = $(this).parent().find('.language-options');
            var $icon = $(this).find('i');
            if($translations.hasClass('hidden')){            
                $translations.removeClass('hidden');
                $icon.removeClass('icon-chevron-down');
                $icon.addClass('icon-chevron-up');
            }
            else{
                $translations.addClass('hidden');
                $icon.addClass('icon-chevron-down');
                $icon.removeClass('icon-chevron-up');
            }
        });
    });    
</script>