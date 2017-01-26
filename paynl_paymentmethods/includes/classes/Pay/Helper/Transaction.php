<?php

class Pay_Helper_Transaction {

    public static function addTransaction($transaction_id, $option_id, $amount, $currency, $order_id, $startData) {
        $db = Db::getInstance();

        $data = array(
            'transaction_id' => $transaction_id,
            'option_id' => (int)$option_id,
            'amount' => (int)$amount,
            'currency' => $currency,
            'order_id' => $order_id,
            'status' => 'NEW',
            'start_data' => $db->escape(json_encode($startData)),
        );

        $db->insert('pay_transactions', $data);
    }

    private static function updateTransactionState($transactionId, $statusText) {
        $db = Db::getInstance();

        $db->update('pay_transactions', array('status' => $statusText), "transaction_id = '" . $db->escape($transactionId) . "'");
    }

    public static function getTransaction($transaction_id) {
        $db = Db::getInstance();

        $sql = "SELECT * FROM " . _DB_PREFIX_ . "pay_transactions WHERE transaction_id = '" . $db->escape($transaction_id) . "'";

        $row = $db->getRow($sql);
        if (empty($row)) {
            throw new Pay_Exception('Transaction not found');
        }
        return $row;
    }

    /**
     * Check if the order is already paid, it is possible that an order has more than 1 transaction.
     * So we heck if another transaction for this order is already paid
     * 
     * @param integer $order_id
     */
    public static function orderPaid($order_id) {
        $db = Db::getInstance();

        $sql = "SELECT * FROM " . _DB_PREFIX_ . "pay_transactions WHERE order_id = '" . $db->escape($order_id) . "' AND status = 'PAID'";

        $row = $db->getRow($sql);
        if (empty($row)) {
            return false;
        } else {
            return true;
        }
    }

    public static function processTransaction($transactionId, $dry_run = false) {

        $token = Configuration::get('PAYNL_TOKEN');
        $serviceId = Configuration::get('PAYNL_SERVICE_ID');

        $apiInfo = new Pay_Api_Info();

        $apiInfo->setApiToken($token);
        $apiInfo->setServiceId($serviceId);
        $apiInfo->setTransactionId($transactionId);

        $result = $apiInfo->doRequest();


        $transactionAmount = $result['paymentDetails']['paidCurrenyAmount'];

        $stateId = $result['paymentDetails']['state'];

        $stateText = self::getStateText($stateId);

        //de transactie ophalen
        try{
            $transaction = self::getTransaction($transactionId);
        } catch (Pay_Exception $ex) {
            // transactie is niet gevonden... quickfix, we voegen hem opnieuw toe
            self::addTransaction($transactionId, $result['paymentDetails']['paymentOptionId'], $result['paymentDetails']['amount'],  $result['paymentDetails']['paidCurrency'], str_replace('CartId: ', '', $result['statsDetails']['extra1']), 'Inserted after not found');
 
            $transaction = self::getTransaction($transactionId);
        }

        $cartId = $orderId = $transaction['order_id'];

        $orderPaid = self::orderPaid($orderId);

        if ($orderPaid == true && $stateText != 'PAID') {
            throw new Pay_Exception('Order already paid');
        }

        if ($stateText == $transaction['status'] || $dry_run) {
            //nothing changed so return without changing anything
            $real_order_id = Order::getOrderByCartId($orderId);
            return array(
                'orderId' => $orderId,
                'state' => $stateText,
                'real_order_id' => $real_order_id,
            );
        }

        //update the transaction state
        self::updateTransactionState($transactionId, $stateText);

        $objOrder = Order::getOrderByCartId($cartId);
        //$objOrder = new Order($orderId);

        $statusPending = Configuration::get('PAYNL_WAIT');
        $statusPaid = Configuration::get('PAYNL_SUCCESS');
        $statusCancel = Configuration::get('PAYNL_CANCEL');


        $id_order_state = '';

        $paid = false;

        if ($stateText == 'PAID') {
            $id_order_state = $statusPaid;

            $module = Module::getInstanceByName(Tools::getValue('module'));

            $cart = new Cart($cartId);
            $customer = new Customer($cart->id_customer);

            $currency = $cart->id_currency;


            $orderTotal = $cart->getOrderTotal();
            $extraFee = $module->getExtraCosts($transaction['option_id'], $orderTotal);

            $cart->additional_shipping_cost += $extraFee;

            $cart->save();

            $paymentMethodName = $module->getPaymentMethodName($transaction['option_id']);

      
            $paidAmount = $transactionAmount / 100;
            


            $module->validateOrderPay((int) $cart->id, $id_order_state, $paidAmount, $extraFee, $paymentMethodName, NULL, array('transaction_id' => $transactionId), (int) $currency, false, $customer->secure_key);

            $real_order_id = Order::getOrderByCartId($cart->id);
        } elseif ($stateText == 'CANCEL') {
            $real_order_id = Order::getOrderByCartId($cartId);

            if ($real_order_id) {
                $objOrder = new Order($real_order_id);
                $history = new OrderHistory();
                $history->id_order = (int) $objOrder->id;
                $history->changeIdOrderState((int) $statusCancel, $objOrder);
                $history->addWithemail();
            }
        } elseif ($stateText == 'PENDING'){
            $id_order_state = $statusPending;

            $module = Module::getInstanceByName(Tools::getValue('module'));

            $cart = new Cart($cartId);
            $customer = new Customer($cart->id_customer);

            $currency = $cart->id_currency;


            $orderTotal = $cart->getOrderTotal();
            $extraFee = $module->getExtraCosts($transaction['option_id'], $orderTotal);

            $cart->additional_shipping_cost += $extraFee;

            $cart->save();

            $paymentMethodName = $module->getPaymentMethodName($transaction['option_id']);


            $paidAmount = 0;


            $module->validateOrderPay((int) $cart->id, $id_order_state, $paidAmount, $extraFee, $paymentMethodName, NULL, array('transaction_id' => $transactionId), (int) $currency, false, $customer->secure_key);

            $real_order_id = Order::getOrderByCartId($cart->id);
        }

        return array(
            'orderId' => $orderId,
            'real_order_id' => $real_order_id,
            'state' => $stateText,
        );
    }

    /**
     * Get the status by statusId
     * 
     * @param int $statusId
     * @return string The status
     */
    public static function getStateText($stateId) {
        switch ($stateId) {
            case 80:
            case -51:
                return 'CHECKAMOUNT';
            case 100:
                return 'PAID';
            default:
                if ($stateId < 0) {
                    return 'CANCEL';
                } else {
                    return 'PENDING';
                }
        }
    }

}
