jQuery(document).ready(function ()
{

    var customFailoverGateway = jQuery("#PAYNL_CUSTOM_FAILOVER_GATEWAY").parents(":eq(1)");

    if (jQuery("#PAYNL_FAILOVER_GATEWAY").val() !== 'custom') {
        customFailoverGateway.css("display", "none");
    }

    jQuery("#PAYNL_FAILOVER_GATEWAY").on('change', function () {
        customFailoverGateway.css("display", ($(this).val() == 'custom' ? "block" : "none"));
    });

    jQuery("#PAY-info-button").click(function () {
        jQuery("#dialog-info-modal").dialog({
            modal: true,
            closeOnEscape: false
        });
    });

    jQuery("#pay-refund-button, #pay-pin-refund-button").click(function () {
        var clickedButtonId = jQuery(this).attr("id");
        var amount = jQuery('#pay-refund-amount').val();
        var errorMessage = jQuery('#pay-lang-invalidamount').val();

        if (!/^[0-9,]+$/.test(amount)) {
            alert(errorMessage);
            return;
        }

        if (amount.indexOf(',') === -1) {
            amount = parseFloat(amount);
        } else {
            amount = parseFloat(amount.replace(',', '.').replace(' ', ''));
        }
        var transactionid = jQuery('#pay-transactionid').val();
        var PrestaOrderId = jQuery('#pay-prestaorderid').val();
        var ajaxurl = jQuery('#pay-ajaxurl').val();
        var presentationAmount = amount.toFixed(2);
        var currency = jQuery('#pay-currency').val();
        var lang_areyoursure = jQuery('#pay-lang-areyoursure').val();
        var lang_refunding = jQuery('#pay-lang-refunding').val();
        var lang_succes = jQuery("#pay-lang-succesfullyrefunded").val() + ': ' + currency + ' ' + presentationAmount;
        var lang_button = jQuery("#pay-lang-refundbutton").val();
        var lang_couldnotprocess = jQuery("#pay-lang-couldnotprocess").val();
        var errorMessage = 'Refund failed';

        if (clickedButtonId === 'pay-refund-button') {
            var callType = 'refund'
        } else if (clickedButtonId === 'pay-pin-refund-button') {
            var callType = 'retourpin'
            var terminalCode = jQuery("#pay-terminalcode").val();
        }

        presentationAmount = presentationAmount.replace('.', ',');

        if (confirm(lang_areyoursure + ': ' + currency + ' ' + presentationAmount + ' ?')) {

            var data = {};
            jQuery.extend(data, {amount: amount});
            jQuery.extend(data, {orderid: transactionid});
            jQuery.extend(data, {prestaorderid: PrestaOrderId});
            jQuery.extend(data, {calltype: callType});

            if (clickedButtonId === 'pay-pin-refund-button') {
                jQuery.extend(data, {returnurl: window.location.href});
                jQuery.extend(data, {terminalcode: terminalCode});
            }


            var actionButton = jQuery(this);
            var payOption = jQuery(this).parent();

            jQuery(actionButton).text(lang_refunding);

            exchangeCall(ajaxurl, data, payOption, lang_succes, actionButton, lang_button, lang_couldnotprocess, errorMessage);

        }
    });

    jQuery("#pay-capture-button").click(function () {
        var amount = jQuery('#pay-capture-amount').val();
        var errorMessage = jQuery('#pay-lang-invalidamount').val();

        if (!/^[0-9,]+$/.test(amount)) {
            alert(errorMessage);
            return;
        }

        if (amount.indexOf(',') === -1) {
            amount = parseFloat(amount);
        } else {
            amount = parseFloat(amount.replace(',', '.').replace(' ', ''));
        }
        var transactionid = jQuery('#pay-transactionid').val();
        var PrestaOrderId = jQuery('#pay-prestaorderid').val();
        var ajaxurl = jQuery('#pay-ajaxurl').val();
        var presentationAmount = amount.toFixed(2);
        var currency = jQuery('#pay-currency').val();
        var lang_areyoursurecapture = jQuery('#pay-lang-areyoursurecapture').val();
        var lang_capturing = jQuery('#pay-lang-capturing').val();
        var lang_succes = jQuery("#pay-lang-succesfullycaptured").val() + ': ' + currency + ' ' + presentationAmount;
        var lang_button = jQuery("#pay-lang-capture-button").val();
        var lang_couldnotprocess = jQuery("#pay-lang-couldnotprocesscapture").val();
        var errorMessage = 'Capture failed';

        presentationAmount = presentationAmount.replace('.', ',');


        if (confirm(lang_areyoursurecapture + ': ' + currency + ' ' + presentationAmount + ' ?')) {

            var data = {};
            jQuery.extend(data, {amount: amount});
            jQuery.extend(data, {orderid: transactionid});
            jQuery.extend(data, {prestaorderid: PrestaOrderId});
            jQuery.extend(data, {calltype: 'capture'});

            var actionButton = jQuery(this);
            var payOption = jQuery(this).parent();

            jQuery(actionButton).text(lang_capturing);

            exchangeCall(ajaxurl, data, payOption, lang_succes, actionButton, lang_button, lang_couldnotprocess, errorMessage);
        }
    });

    jQuery("#pay-capture-remaining-button").click(function () {
        var amount = null;

        var transactionid = jQuery('#pay-transactionid').val();
        var PrestaOrderId = jQuery('#pay-prestaorderid').val();
        var ajaxurl = jQuery('#pay-ajaxurl').val();
        var lang_areyoursurecapture = jQuery('#pay-lang-areyoursurecaptureremaining').val();
        var lang_capturing = jQuery('#pay-lang-capturing').val();
        var lang_succes = jQuery("#pay-lang-succesfullycapturedremaining").val();
        var lang_button = jQuery("#pay-lang-capture-remaining-button").val();
        var lang_couldnotprocess = jQuery("#pay-lang-couldnotprocesscapture").val();
        var errorMessage = 'Capture failed';

        if (confirm(lang_areyoursurecapture)) {

            var data = {};
            jQuery.extend(data, {amount: amount});
            jQuery.extend(data, {orderid: transactionid});
            jQuery.extend(data, {prestaorderid: PrestaOrderId});
            jQuery.extend(data, {calltype: 'capture'});

            var actionButton = jQuery(this);
            var payOption = jQuery(this).parent();

            jQuery(actionButton).text(lang_capturing);

            exchangeCall(ajaxurl, data, payOption, lang_succes, actionButton, lang_button, lang_couldnotprocess, errorMessage);
        }
    });

    jQuery("#pay-version-check").click(function () {
        var current_version = jQuery('#pay-version-check').val();
        getVersion(current_version);
    });

    function getVersion(current_version){
        $.ajax({
            url: 'https://api.github.com/repos/paynl/prestashop1.7-plugin/releases',
            method: 'GET',
            dataType: 'json',
            asynchronous: true,
            success: function (data) {
                var newest_version = data[0].tag_name;
                let result = '';

                if (newest_version.startsWith('v')) {
                    newest_version = newest_version.substring(1);
                }

                if (newest_version > current_version) {
                    result = 'There is a new version available (' + newest_version + ')'
                } else {
                    result = 'You are up to date with the latest version'
                    jQuery('#pay-version-check-current-version').addClass('versionUpToDate');
                }

                jQuery('#pay-version-check').hide();
                jQuery('#pay-version-check-result').html(result);
                jQuery('#pay-version-check-result').css('display', 'block');
            }
        })
    }

    function exchangeCall(ajaxurl, data, payOption, lang_succes, actionButton, lang_button, lang_couldnotprocess, errorMessage){
        setTimeout(function () {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (data) {
                    if (data.success) {
                        if (data.url) {
                            window.location.href = data.url
                        }
                        jQuery('#pay-status').text(' - ');
                        jQuery(payOption).text(lang_succes);
                    } else {
                        jQuery(actionButton).text(lang_button);
                        alert(lang_couldnotprocess);
                    }
                },
                error: function () {
                    jQuery(actionButton).text(lang_button);
                    alert(errorMessage);
                }
            });
        }, 750);
    }

    jQuery("#module_form_submit_btn_fr").click(function () {  
        $('#email_error').hide();
        $('#message_error').hide();
        var email = $('#FR_Email').val();
        var message = $('#FR_Message').val();   
        
        var regex = /^[\w-\.]+@([\w-]+\.)+[\w-]/i;
        if($.trim(message) == '' || ($.trim(email) != '' && !regex.test($('#FR_Email').val()))){        
            if($.trim(email) != '' && !regex.test($('#FR_Email').val())){
                $('#email_error').css('display', 'inline');
            }
            if($.trim(message) == ''){
                $('#message_error').css('display', 'inline');
            }
            return false;
        }
        
        var ajaxurl = $('#pay-ajaxurl').val();        
        var data = {
            'email' : email,
            'message' : message,
            'calltype' : 'feature_request'
        };     
        setTimeout(function () {
            $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function (data) {
                        if (data.success) {
                            $('#FR_Email').val("");
                            $('#FR_Message').val("");
                            $('#FR_Success_Modal').modal('show');                            
                        } else {
                            $('#FR_fail_Modal').modal('show'); 
                        }
                    },
                    error: function () {  
                        $('#FR_fail_Modal').modal('show');                    
                    }
                });
            }, 750);
        
    });

    jQuery('#tas').click(function () {
        toggleAdvancedSettings();
    });

    jQuery('.obscuredInput').each(function () {
        button = jQuery('<a class="obscuredDisplayShow"></a>');
        button.click(function () {
            toggleObscured(this);
        });
        jQuery(this).parent().append(button);
    })
    
    function toggleObscured (element) {
        jQuery(element).parent().find('.obscuredInput').toggleClass('display');
    }

    function toggleAdvancedSettings() {
        jQuery('.advancedSetting').slideToggle(200);
    }

});
