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

            $this->context->cart->id_customer = 0;
            $this->context->cart->id_address_delivery = 0;
            $this->context->cart->id_address_invoice = 0;
            $this->context->cart->update();

            $transaction = $this->module->startPayment($cart, 10, ['fastcheckout' => true]);
            Tools::redirect($transaction);

        } catch (Throwable $th) {

            $link = Context::getContext()->link;
            $refererUrl = $link->getRefererURL();
            Tools::redirect($refererUrl);

        }
    }
}