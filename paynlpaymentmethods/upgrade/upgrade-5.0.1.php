<?php

/**
 * @param PaynlPaymentMethods $module
 * @return bool
 */
function upgrade_module_5_0_1($module)
{
    $currentValue = Configuration::get('PAYNL_FAILOVER_GATEWAY');

    if (empty($currentValue) || str_contains($currentValue, 'rest-api.pay.nl')) {
        Configuration::updateValue('PAYNL_FAILOVER_GATEWAY', 'https://connect.pay.nl');
    }
  
   Configuration::updateValue('PAYNL_SDK_CACHING', true);

    if (method_exists('Tools', 'clearSmartyCache')) {
        Tools::clearSmartyCache();
    }

    if (method_exists('Tools', 'clearAllCache')) {
        Tools::clearAllCache();
    }

    if (Context::getContext()->smarty) {
        Context::getContext()->smarty->clearCompiledTemplate();
    }

    return true;
}
