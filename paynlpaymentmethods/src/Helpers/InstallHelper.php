<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use Db;

/**
 * Class InstallHelper
 *
 * @package PaynlPaymentMethods\PrestaShop\Helpers
 */
class InstallHelper
{
    public function __construct()
    {
        return $this;
    }

    /**
     * @return true
     */
    public function createDatabaseTable()
    {
        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'pay_transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
				        `transaction_id` varchar(255) DEFAULT NULL,
                `cart_id` int(11) DEFAULT NULL,
                `customer_id` int(11) DEFAULT NULL,
                `payment_option_id` int(11) DEFAULT NULL,
                `amount` decimal(20,6) DEFAULT NULL,
                `hash` varchar(255) DEFAULT NULL,
                `order_reference` varchar(255) DEFAULT NULL,
                `status` varchar(255) DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
                INDEX (`transaction_id`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;');

        Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pay_processing` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `payOrderId` varchar(255) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `payOrderId` (`payOrderId`) USING BTREE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;');
        return true;
    }

}