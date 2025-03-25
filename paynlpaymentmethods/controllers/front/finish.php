<?php

use PaynlPaymentMethods\PrestaShop\PayHelper;
use PaynlPaymentMethods\PrestaShop\PaymentMethod;
use PayNL\Sdk\Exception\PayException;

class PaynlPaymentMethodsFinishModuleFrontController extends ModuleFrontController
{
    private $helper;
    private $order = null;
    private $payOrderId = null;
    private $reference = null;
    private $statusAction = null;
    private $statusCode = null;
    private $paymentProfileId = null;

    /**
     * @return void
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->helper = new PayHelper();
        $this->statusAction = Tools::getValue('statusAction');
        $payOrderId = Tools::getValue('id');
        $iAttempt = Tools::getValue('attempt');
        $bValidationDelay = Configuration::get('PAYNL_VALIDATION_DELAY') == 1;
        $this->payOrderId = $payOrderId;
        $this->statusCode = Tools::getValue('statusCode');
        $this->reference = Tools::getValue('reference');

        /**
         * @var $module PaynlPaymentMethods
         */
        $module = $this->module;
        try {
            $transaction = $module->getPayOrder($payOrderId);
            $ppid = $transaction->getPaymentMethod();
            $stateName = $transaction->getStatusName();
            $this->statusCode = $transaction->getStatusCode();
            $this->paymentProfileId = $transaction->getPaymentMethod();

        } catch (PayException $e) {
            $this->helper->payLog('finishPostProcess', 'Could not retrieve transaction', null, $payOrderId);
            return;
        }

        $this->helper->payLog('finishPostProcess', 'Returning to webshop. Method: ' . $ppid . '. Status: ' . $stateName . ', code:' . $this->statusCode, $transaction->getOrderId(), $payOrderId);

        if ($transaction->isPaid() || $transaction->isPending() || $transaction->isBeingVerified() || $transaction->isAuthorized()) {

            if (!$transaction->isPaid()) {
                $iTotalAttempts = in_array($ppid, array(PaymentMethod::METHOD_OVERBOEKING, PaymentMethod::METHOD_SOFORT)) ? 1 : 20;
                if ($bValidationDelay == 1 && $iAttempt < $iTotalAttempts) {
                    # Wait for exchange to process of validationDelay is enabled
                    return;
                }
            }
            if (empty($cart->id_customer)) {
                $cart = $this->context->cart;
            }
            unset($this->context->cart);
            unset($this->context->cookie->id_cart);

            $cartId = $transaction->getReference();
            $orderId = Order::getIdByCartId($cartId);
            if (empty($orderId) && $iAttempt < 1) {
                return;
            }

            $customer = new Customer($cart->id_customer);
            $this->order = $orderId;
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cartId . '&id_module=' . $this->module->id . '&id_order=' . $orderId . '&key=' . $customer->secure_key);
        } else {
            # Delete old payment fee
            $this->context->cart->deleteProduct(Configuration::get('PAYNL_FEE_PRODUCT_ID'), 0);
            if (in_array($this->statusCode, ['-63', '-64'])) {
                $this->createNewCart($this->context->cart);
                $this->errors[] = $this->module->l('The payment has been denied', 'finish');
                $this->redirectWithNotifications('index.php?controller=order&step=1');
            } elseif ($transaction->isCancelled() || $this->paymentProfileId == '138') {
                if ($this->paymentProfileId == '138') {
                    $this->createNewCart($this->context->cart);
                } else {
                    $this->restoreCart();
                }
                $this->errors[] = $this->module->l('The payment has been canceled', 'finish');
                $this->redirectWithNotifications('index.php?controller=order&step=1');
            } else {
                # To checkout
                Tools::redirect('index.php?controller=order&step=1');
            }
        }
    }

    /**
     * @return void
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $iAttempt = Tools::getValue('attempt');
        if (empty($iAttempt)) {
            $iAttempt = 0;
        }
        $arrUrl = parse_url($_SERVER['REQUEST_URI']);

        $iAttempt += 1;
        $url = _PS_BASE_URL_ . $arrUrl['path'] . '?id=' . $this->payOrderId .
            '&reference=' . $this->reference .
            '&statusAction=' . $this->statusAction .
            '&statusCode=' . $this->statusCode .
            '&utm_nooverride=1&attempt=' . $iAttempt;

        $this->context->smarty->assign(array('order' => $this->payOrderId, 'extendUrl' => $url));
        $this->setTemplate('module:paynlpaymentmethods/views/templates/front/waiting.tpl');
    }

    /**
     * @param object $oldCart
     * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
     * @return void
     */
    public function createNewCart($oldCart)
    {
        $newCart = $oldCart->duplicate();
        if (!empty($newCart["cart"]->id)) {
            $this->context->cookie->id_cart = $newCart["cart"]->id;
            $this->context->cookie->write();
            $db = Db::getInstance();
            $sql = new DbQuery();
            $sql->select('checkout_session_data')->from('cart')->where("id_cart = " . $db->escape($oldCart->id))->limit(1);
            $sessionData = $db->executeS($sql)[0]["checkout_session_data"];
            $db->update('cart', ['checkout_session_data' => pSQL($sessionData)], 'id_cart = ' . $db->escape($newCart["cart"]->id));
            $oldCart->delete;
        }
    }

    public function restoreCart()
    {
        $this->context->cookie->id_cart = $this->reference;
        $cart = new Cart($this->context->cookie->id_cart);

        if (Validate::isLoadedObject($cart) && $cart->id_guest == $this->context->cookie->id_guest) {
            $this->context->cart = $cart; // Restore guest's cart
        } else {
            $debugText = 'Restoring cart failed: ' . PHP_EOL .
                'cart  id_quest: ' . $cart->id_guest . PHP_EOL .
                'cookie questid: ' . $this->context->cookie->id_guest;

            $this->helper->payLog('finishPostProcess', $debugText);
        }
    }
}
