jQuery(function () {
    let found = false;
    jQuery(".payment-option img[src*='static.pay'], .payment-option img[src*='paynlpayment']").each(function () {
        jQuery(this).parent().parent().addClass(found ? 'PAYNL' : 'PAYNL firstMethod');
        found = true;
    });
});