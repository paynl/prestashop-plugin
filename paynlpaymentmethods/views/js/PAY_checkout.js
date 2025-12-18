jQuery(document).ready(function () {
    var found = false;
    jQuery(".payment-option IMG[src*='static.pay']").each(function (indexNr) {
        jQuery(this).parent().parent().addClass((found == false ? 'PAYNL firstMethod' : 'PAYNL'));
        found = true;
    });
    jQuery(".payment-option IMG[src*='paynlpayment']").each(function (indexNr) {
        jQuery(this).parent().parent().addClass((found == false ? 'PAYNL firstMethod' : 'PAYNL'));
        found = true;
    });
});