<?php

/**
 * @param $module PaynlPaymentMethods
 */
function upgrade_module_5_0_1($module)
{
    Configuration::updateValue('PAYNL_SDK_CACHING', true);
    return true;
}
