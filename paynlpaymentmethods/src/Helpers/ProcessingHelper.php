<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use PaynlPaymentMethods\PrestaShop\PayHelper;
use Currency;
use OrderHistory;

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

}