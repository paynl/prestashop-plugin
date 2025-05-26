<?php

use PayNL\Sdk\Model\Request\TransactionRefundRequest;
use PayNL\Sdk\Model\Request\OrderCaptureRequest;
use PayNL\Sdk\Exception\PayException;
use PaynlPaymentMethods\PrestaShop\PayHelper;
use PaynlPaymentMethods\PrestaShop\PaymentMethod;

class PaynlPaymentMethodsAjaxModuleFrontController extends ModuleFrontController
{

    /**
     * @return void
     */
    public function init(): void
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
    public function initContent(): void
    {
        $callType = Tools::getValue('calltype');
        $prestaOrderId = (int) Tools::getValue('prestaorderid');
        $amount = (float) Tools::getValue('amount');
        $module = $this->module;
        $helper = new PayHelper();

        if ($callType === 'feature_request') {
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
            $transactionId = $orderPayment->transaction_id ?? '';

            $currency = new Currency((int) $orderPayment->id_currency);
            $strCurrency = $currency->iso_code;

            $cartId = (int)$order->id_cart ?: null;
            $method = 'process' . ucfirst($callType);

            if (!method_exists($this, $method)) {
                $this->returnResponse(false, 0, 'Invalid action');
                return;
            }

            $this->$method($prestaOrderId, $amount, $cartId, $transactionId, $strCurrency, $module);
        } catch (Exception $e) {
            $helper->payLog('Capture', "Failed trying to {$callType} {$amount} on ps-order id {$prestaOrderId}. Error: " . $e->getMessage());
            $this->returnResponse(false, 0, 'Could not find order');
        }
    }

    /**
     * @param int $prestaOrderId
     * @param float $amount
     * @param int|null $cartId
     * @param string $transactionId
     * @param string $strCurrency
     * @param $module
     * @return void
     */
    public function processRefund(int $prestaOrderId, float $amount, ?int $cartId, string $transactionId, string $strCurrency, $module): void
    {
        $helper = new PayHelper();
        $helper->payLog('Refund', "Trying to refund {$amount} {$strCurrency} on order {$prestaOrderId}", $cartId, $transactionId);

        try {
            $request = new TransactionRefundRequest($transactionId, $amount, $strCurrency);
            $request->setConfig($helper->getConfig());
            $refund = $request->start();

            $helper->payLog('Refund', 'Refund success: ' . $refund->getDescription(), $cartId, $transactionId);
            $this->returnResponse(true, $refund->getAmountRefunded()->getValue(), 'successfully_refunded');
        } catch (PayException $e) {
            $helper->payLog('Refund', 'Refund failed: ' . $e->getMessage(), $cartId, $transactionId);
            $this->returnResponse(false, 0, 'could_not_process_refund');
        }
    }

    /**
     * @param int $prestaOrderId
     * @param float $amount
     * @param int|null $cartId
     * @param string $transactionId
     * @param string $strCurrency
     * @param $module
     * @return void
     */
    public function processCapture(int $prestaOrderId, float $amount, ?int $cartId, string $transactionId, string $strCurrency, $module): void
    {
        $helper = new PayHelper();
        $helper->payLog('Capture', "Trying to capture {$amount} {$strCurrency} on order {$prestaOrderId}", $cartId, $transactionId);

        try {
            $request = new OrderCaptureRequest($transactionId, $amount);
            $request->setConfig($helper->getConfig());
            $capture = $request->start();

            $helper->payLog('Capture', 'Capture success: ' . $capture->getDescription(), $cartId, $transactionId);
            $this->returnResponse(true, $capture->getAmount(), 'successfully_captured');
        } catch (PayException $e) {
            $helper->payLog('Capture', 'Capture failed: ' . $e->getMessage(), $cartId, $transactionId);
            $this->returnResponse(false, 0, 'could_not_process_capture');
        }
    }

    /**
     * @param int $prestaOrderId
     * @param float $amount
     * @param int|null $cartId
     * @param string $transactionId
     * @param string $strCurrency
     * @param $module
     * @return void
     */
    public function processPintransaction(int $prestaOrderId, float $amount, ?int $cartId, string $transactionId, string $strCurrency, $module): void
    {
        $returnUrl = Tools::getValue('returnurl');
        $terminalCode = Tools::getValue('terminalcode');
        $helper = new PayHelper();
        $helper->payLog('Pintransaction', 'Trying to start a pin transaction from the admin ' . $amount . ' ' . $strCurrency . ' on prestashop-order id ' . $prestaOrderId, $cartId, $transactionId);

        try {
            $pinTransaction = new PayNL\Sdk\Model\Request\OrderCreateRequest();
            $pinTransaction->setConfig($helper->getConfig());
            $context = $module->getContext();

            $pinTransaction->setServiceId(Tools::getValue('PAYNL_SERVICE_ID', Configuration::get('PAYNL_SERVICE_ID')));
            $pinTransaction->setPaymentMethodId(PaymentMethod::METHOD_PIN);
            $pinTransaction->setAmount($amount);
            $pinTransaction->setCurrency($strCurrency);
            $pinTransaction->setReturnurl($returnUrl);
            $pinTransaction->setExchangeUrl($context->link->getModuleLink($module->name, 'exchange', array(), true));
            $pinTransaction->setTerminal($terminalCode);
            $pinTransaction->setDescription($prestaOrderId);
            $pinTransaction->setReference($prestaOrderId);
            $pinTransaction->setStats((new PayNL\Sdk\Model\Stats)->setExtra1($prestaOrderId)->setObject(($helper->getObjectInfo($module))));

            $payTransaction = $pinTransaction->start();
            $this->returnResponse(true, null, 'successfully_pin', $payTransaction->getPaymentUrl());

            $helper->payLog('Pintransaction', 'Pin transaction in admin started with succes', $cartId, $transactionId);
        } catch (Exception $e) {
            $helper->payLog('Pintransaction', 'Pin transaction in admin failed: ' . $e->getMessage(), $cartId, $transactionId);
            $this->returnResponse(false, null, 'could_not_process_pintransaction');
        }
    }

    /**
     * @param int $prestaOrderId
     * @param float $amount
     * @param int|null $cartId
     * @param string $transactionId
     * @param string $strCurrency
     * @param $module
     * @return void
     */
    public function processRetourpin(int $prestaOrderId, float $amount, ?int $cartId, string $transactionId, string $strCurrency, $module): void
    {
        $helper = new PayHelper();
        $returnUrl = Tools::getValue('returnurl');
        $terminalCode = Tools::getValue('terminalcode');
        $context = $module->getContext();

        $helper->payLog('Retourpin', "Initiating retourpin for order {$prestaOrderId}: {$amount} {$strCurrency}", $cartId, $transactionId);

        try {
            $retourpin = new PayNL\Sdk\Model\Request\OrderCreateRequest();
            $retourpin->setConfig($helper->getConfig())
                ->setServiceId(Tools::getValue('PAYNL_SERVICE_ID', Configuration::get('PAYNL_SERVICE_ID')))
                ->setPaymentMethodId(PaymentMethod::METHOD_RETOURPIN)
                ->setAmount($amount)
                ->setCurrency($strCurrency)
                ->setReturnurl($returnUrl)
                ->setExchangeUrl($context->link->getModuleLink($module->name, 'exchange', [], true))
                ->setTerminal($terminalCode)
                ->setDescription((string) $prestaOrderId)
                ->setReference((string) $prestaOrderId)
                ->setStats((new PayNL\Sdk\Model\Stats())->setExtra1((string) $prestaOrderId)->setObject($helper->getObjectInfo($module)));

            $transaction = $retourpin->start();
            $helper->payLog('Retourpin', 'Retourpin started successfully', $cartId, $transactionId);
            $this->returnResponse(true, '', '', $transaction->getPaymentUrl());
        } catch (Exception $e) {
            $helper->payLog('Retourpin', 'Retourpin failed: ' . $e->getMessage(), $cartId, $transactionId);
            $this->returnResponse(false, '', 'could_not_process_retourpin');
        }
    }

    /**
     * @param $result
     * @param $amountRefunded
     * @param string $message
     * @param $url
     * @return void
     */
    private function returnResponse($result, $amountRefunded = '', string $message = '', $url = null): void
    {
        header('Content-Type: application/json;charset=UTF-8');

        echo json_encode([
            'success' => $result,
            'amountrefunded' => $amountRefunded,
            'message' => $message,
            'url' => $url
        ]);
        exit();
    }

    /**
     * @param $module
     * @param string $email
     * @param string $message
     * @return void
     */
    public function processFeatureRequest($module, string $email = '', string $message = ''): void
    {
        $helper = new PayHelper();
        try {
            $message_HTML = '<p>A client has sent a feature request via Prestashop.</p><br/>';
            if (!empty($email)) {
                $message_HTML .= '<p>Email: ' . htmlspecialchars($email) . '</p>';
            }
            $message_HTML .= '<p>Message:<br/><p style="border:solid 1px #ddd; padding:5px;">' . nl2br(htmlspecialchars($message)) . '</p></p>';
            $message_HTML .= '<p>Plugin version: ' . $module->version . '</p>';
            $message_HTML .= '<p>Prestashop version: ' . _PS_VERSION_ . '</p>';
            $message_HTML .= '<p>PHP version: ' . phpversion() . '</p>';

            Mail::Send(
                (int) Configuration::get('PS_LANG_DEFAULT'),
                'reply_msg',
                'Feature Request - Prestashop',
                [
                    '{firstname}' => 'Pay.',
                    '{lastname}' => 'Plugin Team',
                    '{reply}' => $message_HTML
                ],
                'webshop@pay.nl',
                'Pay. Plugins'
            );

            $this->returnResponse(true, 0, 'successfully_sent');
        } catch (Exception $e) {
            $helper->payLog('FeatureRequest', 'Failed: ' . $e->getMessage());
            $this->returnResponse(false, 0, 'error');
        }
    }
}
