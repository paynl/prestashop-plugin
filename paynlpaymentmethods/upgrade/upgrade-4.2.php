<?php
/**
 * @param $module PaynlPaymentMethods
 */
function upgrade_module_4_2($module)
{
    $results = array();

    $results[] = (bool)$module->createPaymentFeeProduct();
    $results[] = (bool)$module->uninstallOverrides();

    $results[] = (bool)Db::getInstance()->execute(
        'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'paynl_pfee_cart`');

    if(in_array(false, $results)) return false;
    return true;
}