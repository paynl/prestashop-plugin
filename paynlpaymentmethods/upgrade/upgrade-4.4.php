<?php

/**
 * @param $module PaynlPaymentMethods
 */
function upgrade_module_4_4($module)
{
    $results = array();
    $results[] = $module->createDatabaseTable();

    if (in_array(false, $results)) {
        return false;
    }
    return true;
}
