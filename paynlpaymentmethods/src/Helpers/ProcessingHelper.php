<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use PaynlPaymentMethods\PrestaShop\PayHelper;
use PaynlPaymentMethods\PrestaShop\PaymentMethod;
use Currency;
use OrderHistory;
use OrderPayment;

/**
 * Class ProcessingHelper
 *
 * @package PaynlPaymentMethods\PrestaShop\Helpers
 */
class ProcessingHelper
{
    public function __construct()
    {
        return $this;
    }

    /**
     * Update order status
     *
     * @param $orderId
     * @param $orderState
     * @param string $cartId
     * @param string $transactionId
     * @return void
     */
    public function updateOrderHistory($orderId, $orderState, string $cartId = '', string $transactionId = '')
    {
        (new PayHelper())->payLog('updateOrderHistory', 'Update status. orderId: ' . $orderId . '. orderState: ' . $orderState, $cartId, $transactionId);

        $history = new OrderHistory();
        $history->id_order = $orderId;
        $history->changeIdOrderState($orderState, $orderId, true);
        $history->addWs();
    }

    /**
     * @param $cart
     * @param $module
     * @return bool
     */
    public function checkCurrency($cart, $module): bool
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $module->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $order
     * @param $transactionId 
     * @param $payPayments
     * @param $paymentMethodName
     * @return void
     * @throws PrestaShopException
     * @throws Exception
     */
    public function registerPayments($order, $transactionId, $payPayments, $paymentMethodName, $totalAmount)
    {
        $totalPaid = 0;

        if (!empty($payPayments)) {
            $payPayments[] = null;
        }

        foreach ($payPayments as $key => $payPayment) {
            if (!empty($payPayments)) {
                $payAmount = $payPayment['amount']['value'] / 100;
            } else {
                $payAmount = $totalAmount;
            }

            if ($payAmount == 0) {
                continue;
            }

            $totalPaid += $payAmount;
            $orderPayment = null;
            $arrOrderPayment = OrderPayment::getByOrderReference($order->reference);
            $suffix = '';
            if ($key > 0) {
                $suffix = '_' . $key;
                $paymentMethodName = PaymentMethod::getName($transactionId, $payPayment['paymentMethod']['id']);
            }

            foreach ($arrOrderPayment as $objOrderPayment) {
                if ($objOrderPayment->transaction_id == $transactionId . $suffix) {
                    $orderPayment = $objOrderPayment;
                }
            }

            if (empty($orderPayment)) {
                $orderPayment = new OrderPayment();
                $orderPayment->order_reference = $order->reference;
            }
            if (empty($orderPayment->payment_method)) {
                $orderPayment->payment_method = $paymentMethodName;
            }
            if (empty($orderPayment->amount)) {
                $orderPayment->amount = $payAmount;
            }
            if (empty($orderPayment->transaction_id)) {
                $orderPayment->transaction_id = $transactionId . $suffix;
            }
            if (empty($orderPayment->id_currency)) {
                $orderPayment->id_currency = $order->id_currency;
            }

            $orderPayment->save();
        }
        $order->total_paid_real = ($order->total_paid_real ?? 0) + $totalPaid;
        $order->save();
    }

}