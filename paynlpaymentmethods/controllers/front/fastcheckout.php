<?php

class PaynlPaymentMethodsFastcheckoutModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        try {
            $cart = $this->context->cart;
            if (empty($cart->id) || !Validate::isLoadedObject($cart)) {
                $latestCartId = $this->getLatestCartId();
                $cart = new Cart($latestCartId);
            }

            $cart->id_customer = 0;
            $cart->id_address_delivery = 0;
            $cart->id_address_invoice = 0;
            $cart->update();

            $this->context->cart = $cart;

            $transaction = $this->module->startPayment($cart, 10, ['fastcheckout' => true]);
            Tools::redirect($transaction);
        } catch (Throwable $th) {
            $link = Context::getContext()->link;
            $refererUrl = $link->getRefererURL();
            Tools::redirect($refererUrl);
        }
    }

    function getLatestCartId(): int
    {
        $db = Db::getInstance();
        $sql = '
            SELECT MAX(id_cart)
            FROM `' . _DB_PREFIX_ . 'cart`
            ORDER BY id_cart DESC
        ';
        $latestCartId = $db->getValue($sql);
        return (int) $latestCartId;
    }
}