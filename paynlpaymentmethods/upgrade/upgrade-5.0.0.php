<?php

/**
 * @param $module PaynlPaymentMethods
 */
function upgrade_module_5_0_0($module)
{
    Configuration::updateValue('PAYNL_FAILOVER_GATEWAY', 'https://connect.pay.nl');
    return true;
}
