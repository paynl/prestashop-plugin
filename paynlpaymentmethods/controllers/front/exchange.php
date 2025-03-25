<?php

use PayNL\Sdk\Util\ExchangeResponse;
use PaynlPaymentMethods\PrestaShop\Transaction;
use PaynlPaymentMethods\PrestaShop\PayHelper;
use PayNL\Sdk\Util\Exchange;

class PaynlPaymentMethodsExchangeModuleFrontController extends ModuleFrontController
{
    private string $payOrderId;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $exchange = new Exchange();
        $config = (new PayHelper())->getConfig();

        try {
            $this->payOrderId = $exchange->getPayOrderId();
            $this->checkProcessing($exchange);
            $payOrder = $exchange->process($config);
        } catch (Exception $e) {
            exit('FALSE|Exception: ' . $e->getMessage());
        }

        if ($payOrder->isPending()) {
            $eResponse = new ExchangeResponse(true, 'Ignoring pending');

        } elseif ($payOrder->isPartialPayment()) {
            $eResponse = new ExchangeResponse(true, 'Processing partial payment');

        } elseif ($payOrder->isBeingVerified()) {
            $eResponse = new ExchangeResponse(true, 'Ignoring verified');
        } else {
            try {
                $eResponse = $this->module->processPayment($this->payOrderId, $payOrder);
            } catch (Exception $e) {
                $responseDescription = $e->getMessage();
                $responseDescription = empty($responseDescription) ? 'Error, No message ' : $responseDescription;
                $eResponse = new ExchangeResponse(false, 'Exception: ' . $responseDescription);
            }
        }

        $this->checkProcessing($exchange, 'out');
        $exchange->setExchangeResponse($eResponse);
    }

    /**
     * @param Exchange $exchange
     * @param string $mode
     * @return void
     * @throws \PayNL\Sdk\Exception\PayException
     */
    private function checkProcessing(Exchange $exchange, string $mode = 'in')
    {
        if ($exchange->eventPaid(true)) {
            if ($mode == 'in') {
                $processing = Transaction::checkProcessing($this->payOrderId);
                if (!empty($processing)) {
                    $exchange->setResponse(false, 'Already Processing payment');
                }
            } else {
                Transaction::removeProcessing($this->payOrderId);
            }
        }
    }
}