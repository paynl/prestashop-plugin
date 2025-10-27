<?php

class PaynlPaymentMethodsFastcheckoutModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $context = Context::getContext();
        $cart = $context->cart;

        $transaction = $this->module->startPayment($cart, 10, ['fastcheckout' => true]);
        Tools::redirect($transaction);
    }
}