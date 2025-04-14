<?php

/**
 * @param PaynlPaymentMethods $module
 * @return bool
 */
function upgrade_module_5_0_1($module)
{
    $currentValue = Configuration::get('PAYNL_FAILOVER_GATEWAY');

    if (empty($currentValue) || $currentValue === 'https://rest-api.pay.nl') {
        Configuration::updateValue('PAYNL_FAILOVER_GATEWAY', 'https://connect.pay.nl');
    }

    // Forceer cache flush zodat oude smarty templates geen problemen veroorzaken
    if (method_exists('Tools', 'clearSmartyCache')) {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
    }

    if (method_exists('Tools', 'clearAllCache')) {
        Tools::clearAllCache();
    }

    // Extra: forceer hercompilatie van smarty templates
    if (isset($module->context->smarty)) {
        $module->context->smarty->clearCompiledTemplate(null, null, null, true);
    }

    return true;
}
