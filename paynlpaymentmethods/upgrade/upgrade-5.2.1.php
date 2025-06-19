<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_5_2_1($module)
{
    $db = Db::getInstance();

    $indexes = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . "pay_transactions` WHERE Key_name = 'idx_cart_id'");

    if (empty($indexes)) {
        $db->execute("ALTER TABLE `" . _DB_PREFIX_ . "pay_transactions` ADD INDEX `idx_cart_id` (`cart_id`)");
    }

    return true;
}
