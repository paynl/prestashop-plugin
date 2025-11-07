<?php

class PaynlPaymentMethodsFastcheckoutModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $this->helper = new PayHelper();

        try {
            $cart = $this->context->cart;

            $cart->id_customer = 0;
            $cart->id_address_delivery = 0;
            $cart->id_address_invoice = 0;
            $cart->update();

            $this->context->cart = $cart;

            $transaction = $this->module->startPayment($cart, 10, ['fastcheckout' => true]);
            Tools::redirect($transaction);
        } catch (Throwable $th) {
            $this->helper->payLog('fastcheckout', 'could not start fast checkout: ' . $th->getMessage(), null, null);

            $link = Context::getContext()->link;
            $refererUrl = $link->getRefererURL();
            Tools::redirect($refererUrl);
        }
    }
}