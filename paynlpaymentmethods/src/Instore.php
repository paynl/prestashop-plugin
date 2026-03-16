<?php

namespace PaynlPaymentMethods\PrestaShop;

use PaynlPaymentMethods\PrestaShop\PaymentMethod;
use db;

class Instore extends PaymentMethod
{
    /**
     * Check the status of the pin
     *
     * @param string $hash
     * @param string $transactionId
     * @param string $object
     * @return \Paynl\Result\Instore\Status
     */
    public static function handlePin($hash, $transactionId, $object)
    {
        try {
            $status = \Paynl\Instore::status(['hash' => $hash]);
            Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "pay_transactions` SET `status` = '" . pSQL($status->getTransactionState()) . "', `updated_at` = NOW() WHERE `transaction_id` = '" . pSQL($transactionId) . "'");
            if (in_array($status->getTransactionState(), ['cancelled', 'expired', 'error'])) {
                $object->errors[] = $object->module->l('The payment could not be completed', 'finish');
                $object->redirectWithNotifications('index.php?controller=order&step=1');
            }
            return $status;
        } catch (Exception $objException) {
            $object->errors[] = $object->module->l('The payment could not be completed due to an error. Error: ', 'finish') . $objException->getMessage();
            $object->redirectWithNotifications('index.php?controller=order&step=1');
        }
    }
}
