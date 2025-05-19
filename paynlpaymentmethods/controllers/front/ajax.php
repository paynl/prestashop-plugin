<?php

use PayNL\Sdk\Model\Request\TransactionRefundRequest;
use PayNL\Sdk\Model\Request\OrderCaptureRequest;
use PayNL\Sdk\Exception\PayException;
use PaynlPaymentMethods\PrestaShop\PayHelper;
use PaynlPaymentMethods\PrestaShop\PaymentMethod;
use \PaynlPaymentMethods\PrestaShop\Transaction;

/**
 * @since 1.5.0
 */
class PaynlPaymentMethodsAjaxModuleFrontController extends ModuleFrontController
{

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        if (!$this->isAdminSessionValid()) {
            header('HTTP/1.1 403 Forbidden');
            $this->errors[] = $this->module->l('Access Denied: You do not have permission to access this page.');
            $this->redirectWithNotifications('index.php');
            exit();
        }
    }

    /**
     * @return boolean
     */
    private function isAdminSessionValid()
    {
        $cookie = new Cookie('psAdmin');
        if (isset($cookie->id_employee)) {
            return true;
        }
        return false;
    }

    /**
     * @return void
     */
    public function initContent()
    {
        $callType = Tools::getValue('calltype');
        $prestaOrderId = Tools::getValue('prestaorderid');
        $amount = Tools::getValue('amount');
        $module = $this->module;
        $helper = new PayHelper();

        if ($callType == 'feature_request') {
            $email = Tools::getValue('email');
            $message = Tools::getValue('message');
            $this->processFeatureRequest($module, $email, $message);
            return;
        }
        try {
            $order = new Order($prestaOrderId);

            if (empty($order->id)) {
                throw new Exception('Order not found');
            }

            $paymentCollection = $order->getOrderPayments();
            $orderPayment = reset($paymentCollection);
            $transactionId = $orderPayment->transaction_id;

            $currencyId = $orderPayment->id_currency;
            $currency = new Currency($currencyId);
            $strCurrency = $currency->iso_code;

            $cartId = !empty($order->id_cart) ? $order->id_cart : null;

            $callType = 'process' . ucfirst($callType);

            $this->$callType($prestaOrderId, $amount, $cartId, $transactionId, $strCurrency, $module);
        } catch (Exception $e) {
            $amount = empty($amount) ? '' : $amount;
            $helper->payLog('Capture', 'Failed trying to ' . $callType . ' ' . $amount . ' on ps-order id ' . $prestaOrderId . ' Order not found. Errormessage: ' . $e->getMessage());
            $this->returnResponse(false, 0, 'Could not find order');
        }
    }

    /**
     * @param $prestaOrderId
     * @param $amount
     * @param $cartId
     * @param $transactionId
     * @param $strCurrency
     * @param $module
     */
    public function processRefund($prestaOrderId, $amount, $cartId, $transactionId, $strCurrency, $module)
    {
        $helper = new PayHelper();

        $helper->payLog('Refund', 'Trying to refund ' . $amount . ' ' . $strCurrency . ' on prestashop-order id ' . $prestaOrderId, $cartId, $transactionId);

        try {
            $transactionRefundRequest = new TransactionRefundRequest($transactionId, $amount, $strCurrency);
            $transactionRefundRequest->setConfig($helper->getConfig());

            $refund = $transactionRefundRequest->start();

            $amountRefunded = $refund->getAmountRefunded()->getValue();

            $desc = $refund->getDescription();
            $helper->payLog('Refund', 'Refund success, result message: ' . $desc, $cartId, $transactionId);

            $this->returnResponse(true, $amountRefunded, 'successfully_refunded ' . $strCurrency . ' ' . $amount);
        } catch (PayException $e) {
            $helper->payLog('Refund', 'Refund failed: ' . $e->getMessage(), $cartId, $transactionId);
            $this->returnResponse(false, 0, 'could_not_process_refund');
        }
    }

    /**
     * @param $prestaOrderId
     * @param $amount
     * @param $cartId
     * @param $transactionId
     * @param $strCurrency
     * @param $module
     * @return void
     */
    public function processRetourpin($prestaOrderId, $amount, $cartId, $transactionId, $strCurrency, $module)
    {
        $returnUrl = Tools::getValue('returnurl');
        $terminalCode = Tools::getValue('terminalcode');
        $helper = new PayHelper();
        $helper->payLog('Retourpin', 'Trying to make a retourpin ' . $amount . ' ' . $strCurrency . ' on prestashop-order id ' . $prestaOrderId, $cartId, $transactionId);

        try {
            $retourpin = new PayNL\Sdk\Model\Request\OrderCreateRequest();
            $retourpin->setConfig($helper->getConfig());
            $context = $module->getContext();

            $retourpin->setServiceId(Tools::getValue('PAYNL_SERVICE_ID', Configuration::get('PAYNL_SERVICE_ID')));
            $retourpin->setPaymentMethodId(PaymentMethod::METHOD_RETOURPIN);
            $retourpin->setAmount($amount);
            $retourpin->setCurrency($strCurrency);
            $retourpin->setReturnurl($returnUrl);
            $retourpin->setExchangeUrl($context->link->getModuleLink($module->name, 'exchange', array(), true));
            $retourpin->setTerminal($terminalCode);
            $retourpin->setDescription($prestaOrderId);
            $retourpin->setReference($prestaOrderId);
            $retourpin->setStats((new PayNL\Sdk\Model\Stats)->setExtra1($prestaOrderId)->setObject(($helper->getObjectInfo($module))));

            $payTransaction = $retourpin->start();
            $this->returnResponse(true, null, null, $payTransaction->getPaymentUrl());

            $helper->payLog('Retourpin', 'Retourpin started with succes', $cartId, $transactionId);
        } catch (Exception $e) {
            $helper->payLog('Retourpin', 'Retourpin failed: ' . $e->getMessage(), $cartId, $transactionId);
            $this->returnResponse(false, null, 'could_not_process_retourpin');
        }
    }

    /**
     * @param $prestaOrderId
     * @param $amount
     * @param $cartId
     * @param $transactionId
     * @param $strCurrency
     * @param $module
     */
    public function processCapture($prestaOrderId, $amount, $cartId, $transactionId, $strCurrency, $module)
    {
        $helper = new PayHelper();

        $helper->payLog('Capture', 'Trying to capture ' . $amount . ' ' . $strCurrency . ' on prestashop-order id ' . $prestaOrderId, $cartId, $transactionId);

        try {
            $captureRequest = new OrderCaptureRequest($transactionId, $amount);
            $captureRequest->setConfig($helper->getConfig());

            $capture = $captureRequest->start();

            $amountCaptured = $capture->getAmount();

            $helper->payLog('Capture', 'Capture success, result message: ' . $capture->getDescription(), $cartId, $transactionId);

            $this->returnResponse(true, $amountCaptured, 'successfully_captured ' . $strCurrency . ' ' . $amount);
        } catch (PayException $e) {
            $helper->payLog('Capture', 'Capture failed: ' . $e->getMessage(), $cartId, $transactionId);
            $this->returnResponse(false, 0, 'could_not_process_capture');
        }
    }

    /**
     * @param $result
     * @param string $amountRefunded
     * @param string $message
     * @param $url
     * @return void
     */
    private function returnResponse($result, string $amountRefunded = '', string $message = '', $url = null)
    {
        header('Content-Type: application/json;charset=UTF-8');

        $returnarray = array(
            'success' => $result,
            'amountrefunded' => $amountRefunded,
            'message' => $message,
            'url' => $url
        );

        die(json_encode($returnarray));
    }

    /**
     * @param string $module
     * @param string $email
     * @param string $message
     * @return void
     */
    public function processFeatureRequest(string $module, string $email = '', string $message = ''): void
    {
        $helper = new PayHelper();
        try {
            $message_HTML = '<p>A client has sent a feature request via Prestashop.</p><br/>';
            if (!empty($email)) {
                $message_HTML .= '<p> Email: ' . $email . '</p>';
            }
            $message_HTML .= '<p> Message: <br/><p style="border:solid 1px #ddd; padding:5px;">' . nl2br($message) . '</p></p>';
            $message_HTML .= '<p> Plugin version: ' . $module->version . '</p>';
            $message_HTML .= '<p> Prestashop version: ' . _PS_VERSION_ . '</p>';
            $message_HTML .= '<p> PHP version: ' . substr(phpversion(), 0, 3) . '</p>';
            Mail::Send(
              (int) (Configuration::get('PS_LANG_DEFAULT')), // Defaut language id
              'reply_msg', // Email template file to be use
              ' Feature Request - Prestashop', // Email subject
              array(
                '{firstname}' => 'Pay.',
                '{lastname}' => 'Plugin Team',
                '{reply}' => $message_HTML,
              ),
              'webshop@pay.nl', // Receiver email address
              'Pay. Plugins', // Receiver name
              null, // From email address
              null // From name
            );
            $this->returnResponse(true, 0, 'successfully_sent');
        } catch (Exception $e) {
            $helper->payLog('FeatureRequest', 'Failed:' . $e->getMessage());
            $this->returnResponse(false, 0, 'error');
        }
    }

}
