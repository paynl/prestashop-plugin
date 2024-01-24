<?php


namespace PaynlPaymentMethods\PrestaShop;

use Db;
use DbQuery;

class Transaction
{

    /**
     * Adds the transaction to the pay_transactions table
     *
     * @param integer $transaction_id
     * @param integer $cart_id
     * @param integer $customer_id
     * @param integer $payment_option_id
     * @param float $amount
     *
     */
    public static function addTransaction($transaction_id, $cart_id, $customer_id, $payment_option_id, $amount)
    {
        $db = Db::getInstance();

        $data = array(
            'transaction_id' => $transaction_id,
            'cart_id' => $cart_id,
            'customer_id' => $customer_id,
            'payment_option_id' => $payment_option_id,
            'amount' => $amount
        );

        $db->insert('pay_transactions', $data);
    }

    /**
     * Adds the pinnterminal hash to the transaction, this is required for the instore option
     *
     * @param int $transaction_id
     * @param string $hash
     *      
     */
    public static function addTransactionHash($transaction_id, $hash)
    {
        $db = Db::getInstance();

        $sql = "UPDATE `" . _DB_PREFIX_ . "pay_transactions` SET `hash` = '" . Db::getInstance()->escape($hash) . "', `updated_at` = now() WHERE `" . _DB_PREFIX_ . "pay_transactions`.`transaction_id` = '" . Db::getInstance()->escape($transaction_id) . "';";
        $db->execute($sql);
    }

    /**
     *  Returns the transaction based on transaction_id from the pay_transactions table
     * @param $transaction_id
     * @return array
     */
    public static function get($transaction_id)
    {
        $result = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "pay_transactions` WHERE `transaction_id` = '" . Db::getInstance()->escape($transaction_id) . "';");

        return is_array($result) ? $result : array();
    }

    /**
     * @param $transactionId
     * @param $amount
     * @param $strCurrency
     * @return array
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public static function doRefund($transactionId, $amount = null, $strCurrency = null)
    {
        try {
            PayHelper::sdkLogin();
            $result = true;
            $refundResult = \Paynl\Transaction::refund($transactionId, $amount, null, null, null, $strCurrency);
        } catch (Exception $objException) {
            $refundResult = $objException->getMessage();
            $result = false;
        }

        return array('result' => $result, 'data' => $refundResult);
    }

    /**
     * @param $transactionId
     * @param $amount
     * @return array
     * @throws \Paynl\Error\Api
     * @throws \Paynl\Error\Error
     * @throws \Paynl\Error\Required\ApiToken
     * @throws \Paynl\Error\Required\ServiceId
     */
    public static function doCapture($transactionId, $amount = null)
    {
        try {
            PayHelper::sdkLogin();
            $result = true;
            $captureResult = \Paynl\Transaction::capture($transactionId, $amount);
        } catch (Exception $objException) {
            $captureResult = $objException->getMessage();
            $result = false;
        }

        return array('result' => $result, 'data' => $captureResult);
    }

    /**
     * @param $payOrderId
     * @return array
     */
    public static function checkProcessing($payOrderId)
    {
        try {
            $db = Db::getInstance();
            $payOrderId = $db->escape($payOrderId);
            $sql = new DbQuery();
            $sql->select('*');
            $sql->from('pay_processing');
            $sql->where("payOrderId = '" . $payOrderId . "'");
            $sql->where("created_at > date_sub('" . date('Y-m-d H:i:s') . "', interval 1 minute)");
            $result = $db->executeS($sql);
            if (empty($result)) {
                $db->insert('pay_processing', ['payOrderId' => $payOrderId, 'created_at' => date('Y-m-d H:i:s')], false, false, Db::ON_DUPLICATE_KEY, true);
            }
        } catch (\Exception $e) {
            $result = array();
        }

        return is_array($result) ? $result : array();
    }

    /**
     * @param $payOrderId
     * @return void
     */
    public static function removeProcessing($payOrderId)
    {
        Db::getInstance()->delete('pay_processing', 'payOrderId = "' . $payOrderId . '"');
    }

    /**
     * @param $transactionId
     * @param $orderId
     * @param $paymentOptionId
     * @return void
     */
    public static function updatePaymentMethod($transactionId, $orderId, $paymentOptionId)
    {
        $db = Db::getInstance();

        // Update Pay. transaction table
        $sql = "UPDATE `" . _DB_PREFIX_ . "pay_transactions` SET `payment_option_id` = '" . Db::getInstance()->escape($paymentOptionId) . "', `updated_at` = now() WHERE `" . _DB_PREFIX_ . "pay_transactions`.`transaction_id` = '" . Db::getInstance()->escape($transactionId) . "';"; // phpcs:ignore
        $db->execute($sql);

        // Lookup new payment method name id
        $paymentOption = PaymentMethod::getName($transactionId, $paymentOptionId);

        // Update prestashop payment table
        $sql = "UPDATE `" . _DB_PREFIX_ . "order_payment` SET `payment_method` = '" . Db::getInstance()->escape($paymentOption) . "' WHERE `" . _DB_PREFIX_ . "order_payment`.`transaction_id` = '" . Db::getInstance()->escape($transactionId) . "';"; // phpcs:ignore
        $db->execute($sql);

        // Update prestashop order table
        $sql = "UPDATE `" . _DB_PREFIX_ . "orders` SET `payment` = '" . Db::getInstance()->escape($paymentOption) . "' WHERE `" . _DB_PREFIX_ . "orders`.`id_order` = '" . Db::getInstance()->escape($orderId) . "';"; // phpcs:ignore
        $db->execute($sql);
    }
}
