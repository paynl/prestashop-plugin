<?php

/**
 * @phpcs:disable Squiz.Commenting.FunctionComment.TypeHintMissing
 */

if (!class_exists('\Paynl\Paymentmethods')) {
    $autoload_location = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload_location)) {
        require_once $autoload_location;
    }
}

use PayNL\Sdk\Model\Request\OrderCaptureRequest;
use PayNL\Sdk\Model\Request\TransactionRefundRequest;
use PayNL\Sdk\Model\Product as PayProduct;
use PayNL\Sdk\Util\ExchangeResponse;
use PaynlPaymentMethods\PrestaShop\PayHelper;
use PaynlPaymentMethods\PrestaShop\Helpers\FormHelper;
use PaynlPaymentMethods\PrestaShop\Model\PayConnection;
use PaynlPaymentMethods\PrestaShop\Helpers\PaymentMethodsHelper;
use PaynlPaymentMethods\PrestaShop\Helpers\ProcessingHelper;
use PaynlPaymentMethods\PrestaShop\Helpers\AddressHelper;
use PaynlPaymentMethods\PrestaShop\Helpers\InstallHelper;
use PaynlPaymentMethods\PrestaShop\Transaction;
use PaynlPaymentMethods\PrestaShop\PaymentMethod;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaynlPaymentMethods extends PaymentModule
{
    private $statusPending;
    private $statusPaid;
    private $statusRefund;
    private $statusCanceled;

    private PayHelper $helper;
    private FormHelper $formHelper;
    private ProcessingHelper $processingHelper;
    private AddressHelper $addressHelper;
    private InstallHelper $installHelper;
    private PaymentMethodsHelper $paymentMethodsHelper;

    public array $avMethods = [];
    public PayConnection $payConnection;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->payConnection = new PayConnection();
        $this->name = 'paynlpaymentmethods';
        $this->tab = 'payments_gateways';
        $this->version = '5.0.1';
        $this->ps_versions_compliancy = array('min' => '8.0.0', 'max' => _PS_VERSION_);
        $this->author = 'Pay.';
        $this->controllers = array('startPayment', 'finish', 'exchange');
        if (property_exists($this, 'is_eu_compatible')) {
            $this->is_eu_compatible = 1;
        }

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->bootstrap = true;
        $this->helper = new PayHelper();
        $this->processingHelper = new ProcessingHelper();
        $this->formHelper = new FormHelper();
        $this->addressHelper = new AddressHelper();
        $this->installHelper = new InstallHelper();
        $this->paymentMethodsHelper = new PaymentMethodsHelper();

        parent::__construct();
        $this->statusPending = Configuration::get('PS_OS_CHEQUE');
        $this->statusPaid = Configuration::get('PS_OS_PAYMENT');
        $this->statusCanceled = Configuration::get('PS_OS_CANCELED');
        $this->statusRefund = Configuration::get('PS_OS_REFUND');
        $this->displayName = $this->l('Pay.');
        $this->description = $this->l('Pay. Payment Methods for PrestaShop');
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }

        if (!$this->isRegisteredInHook('displayAdminOrder')) {
            $this->registerHook('displayAdminOrder');
        }

        if (!$this->isRegisteredInHook('displayPaymentReturn')) {
            $this->registerHook('displayPaymentReturn');
        }

        if (!$this->isRegisteredInHook('displayHeader')) {
            $this->registerHook('displayHeader');
        }

        if (!$this->isRegisteredInHook('actionAdminControllerSetMedia')) {
            $this->registerHook('actionAdminControllerSetMedia');
        }

        if (!$this->isRegisteredInHook('actionOrderStatusPostUpdate')) {
            $this->registerHook('actionOrderStatusPostUpdate');
        }

        if ($this->isRegisteredInHook('actionProductCancel')) {
            $this->unregisterHook('actionProductCancel');
        }

        if (!$this->isRegisteredInHook('actionOrderSlipAdd')) {
            $this->registerHook('actionOrderSlipAdd');
        }
    }

    /**
     * @param array $params
     * @return void
     */
    public function hookDisplayHeader(array $params)
    {
        $this->context->controller->addJs($this->_path . 'views/js/PAY_checkout.js');
        $this->context->controller->addCSS($this->_path . 'views/css/PAY_checkout.css');
    }

    /**
     * @param array $params
     * @return void
     */
    public function hookDisplayPaymentReturn(array $params)
    {
        return '';
    }

    /**
     * @return boolean
     */
    public function install(): bool
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('displayPaymentReturn') || !$this->registerHook('displayAdminOrder') || !$this->registerHook(
            'actionAdminControllerSetMedia'
          ) || !$this->registerHook('displayHeader') || !$this->registerHook('actionOrderStatusPostUpdate')) {
            return false;
        }

        $this->createPaymentFeeProduct();
        $this->createDatabaseTable();
        return true;
    }

    /**
     * @return boolean
     */
    public function createDatabaseTable(): bool
    {
        Db::getInstance()->execute(
          'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pay_transactions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
				        `transaction_id` varchar(255) DEFAULT NULL,
                `cart_id` int(11) DEFAULT NULL,
                `customer_id` int(11) DEFAULT NULL,
                `payment_option_id` int(11) DEFAULT NULL,
                `amount` decimal(20,6) DEFAULT NULL,
                `hash` varchar(255) DEFAULT NULL,
                `order_reference` varchar(255) DEFAULT NULL,
                `status` varchar(255) DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
                INDEX (`transaction_id`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );
        Db::getInstance()->execute(
          'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pay_processing` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `payOrderId` varchar(255) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `payOrderId` (`payOrderId`) USING BTREE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );
        return true;
    }

    /**
     * @return void
     */
    public function hookActionAdminControllerSetMedia()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/PAY_v501.css');
        $this->context->controller->addJS($this->_path . 'views/js/PAY_v501.js');
    }

    /**
     * @param array $params
     * @return boolean|string|void
     */
    public function hookDisplayAdminOrder(array $params)
    {
        
        try {
            $cartId = Cart::getCartIdByOrderId((int)$params['id_order']);
            $orderId = Order::getIdByCartId($cartId);
            $order = new Order($orderId);
        } catch (Exception $e) {
            return;
        }

        # Check if the order is processed by Pay.
        if ($order->module !== 'paynlpaymentmethods') {
            return;
        }

        $orderPayments = $order->getOrderPayments();
        $orderPayment = reset($orderPayments);
        $currency = new Currency($orderPayment->id_currency);
        $transactionId = $orderPayment->transaction_id;
        $payOrderAmount = 0;
        $alreadyRefunded = 0;

        try {
            $payOrder = $this->getPayOrder((string)$transactionId);
            if ($payOrder->isPaid() || $payOrder->isAuthorized()) {
                $payGmsOrder = $this->getPayRefundOrder($transactionId);
                $this->helper->payLog('hookDisplayAdminOrder', 'gms: ' . $payGmsOrder->getStatusName());
                if ($payGmsOrder->isRefunded()) {
                    $payOrder = $payGmsOrder;
                }
                $alreadyRefunded = $payGmsOrder->getAmountRefunded();
            }

            $payOrderAmount = $payOrder->getAmount();
            $status = $payOrder->getStatusName();
            $profileId = $payOrder->getPaymentMethod();
            $methodName = PaymentMethod::getName($transactionId, $profileId);
            $showCaptureButton = $payOrder->isAuthorized();
            $showCaptureRemainingButton = $payOrder->getStatusCode() == 97;
            $showRefundButton = ($payOrder->isPaid() || $payOrder->isRefundedPartial()) && ($profileId != PaymentMethod::METHOD_INSTORE_PROFILE_ID && $profileId != PaymentMethod::METHOD_INSTORE); // phpcs:ignore
        } catch (Exception $exception) {
            $showRefundButton = false;
            $showCaptureButton = false;
            $showCaptureRemainingButton = false;
        }

        $amountFormatted = number_format(($order->total_paid - $alreadyRefunded), 2, ',', '.');
        $amountPayFormatted = number_format($payOrderAmount, 2, ',', '.');

        $this->context->smarty->assign(array(
          'lang' => $this->getMultiLang(),
          'this_version' => $this->version,
          'PrestaOrderId' => $orderId,
          'amountCart' => number_format(($order->getOrdersTotalPaid() ?? 0), 2, ',', '.'),
          'amountFormatted' => $amountFormatted,
          'amountPayFormatted' => $amountPayFormatted,
          'currency' => $currency->iso_code,
          'pay_orderid' => $transactionId,
          'status' => $status ?? 'unavailable',
          'method' => $methodName ?? 'Pay.',
          'ajaxURL' => $this->context->link->getModuleLink($this->name, 'ajax', array(), true),
          'showRefundButton' => $showRefundButton,
          'showCaptureButton' => $showCaptureButton,
          'showCaptureRemainingButton' => $showCaptureRemainingButton,
        ));
        return $this->display(__FILE__, 'payorder.tpl');
    }

    /**
     * @param $params
     * @return void
     * @throws \PayNL\Sdk\Exception\PayException
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        try {
            $orderId = (int)$params['id_order'];
            $cartId = Cart::getCartIdByOrderId($orderId);
            $order = new Order($orderId);
        } catch (Exception $e) {
            return;
        }

        # Check if the order is processed by Pay.
        if ($order->module !== 'paynlpaymentmethods') {
            return;
        }

        $bShouldCapture = ($params['newOrderStatus']->shipped == 1 && Configuration::get('PAYNL_AUTO_CAPTURE'));
        $bShouldVoid = ($params['newOrderStatus']->template == "order_canceled" && Configuration::get('PAYNL_AUTO_VOID'));

        if ($bShouldCapture || $bShouldVoid) {
            $action = $bShouldCapture ? 'capture' : 'void';
            $orderPayments = $order->getOrderPayments();
            $orderPayment = reset($orderPayments);
            $transactionId = $orderPayment->transaction_id;
            $transaction = $this->getPayOrder($transactionId);

            # Check if status is Authorized
            if ($transaction->isAuthorized()) {
                $this->helper->payLog('Auto-' . $action, 'Starting auto-' . $action, $cartId, $transactionId);
                try {
                    if ($action == 'capture') {
                        $request = new OrderCaptureRequest($transactionId);
                    } else {
                        $request = new \PayNL\Sdk\Model\Request\OrderVoidRequest($transactionId);
                    }
                    $request->setConfig($this->helper->getConfig())->start();

                    $this->helper->payLog('Auto-' . $action, ucfirst($action) . ' success ', $transactionId);
                } catch (Exception $e) {
                    $this->helper->payLog('Auto-' . $action, $action . ' failed (' . $e->getMessage() . ') ', $cartId, $transactionId);
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getMultiLang(): array
    {
        $lang['title'] = $this->l('Pay.');
        $lang['are_you_sure'] = $this->l('Are you sure want to refund this amount');
        $lang['are_you_sure_capture'] = $this->l('Are you sure you want to capture this transaction for this amount');
        $lang['are_you_sure_capture_remaining'] = $this->l('Are you sure you want to capture the remaining amount of this transaction?');
        $lang['refund_button'] = $this->l('REFUND');
        $lang['capture_button'] = $this->l('CAPTURE');
        $lang['capture_remaining_button'] = $this->l('CAPTURE REMAINING');
        $lang['my_text'] = $this->l('Are you sure?');
        $lang['refund_not_possible'] = $this->l('Refund is not possible');
        $lang['amount_to_refund'] = $this->l('Amount to refund');
        $lang['amount_to_capture'] = $this->l('Amount to capture');
        $lang['refunding'] = $this->l('Processing');
        $lang['capturing'] = $this->l('Processing');
        $lang['currency'] = $this->l('Currency');
        $lang['amount'] = $this->l('Amount');
        $lang['invalidamount'] = $this->l('Invalid amount');
        $lang['successfully_refunded'] = $this->l('Succesfully refunded');
        $lang['successfully_captured'] = $this->l('Succesfully captured');
        $lang['successfully_captured_remaining'] = $this->l('Succesfully captured the remaining amount.');
        $lang['paymentmethod'] = $this->l('Paymentmethod');
        $lang['could_not_process_refund'] = $this->l('Could not process refund. Refund might be too fast or amount is invalid');
        $lang['could_not_process_capture'] = $this->l('Could not process this capture.');
        $lang['info_refund_title'] = $this->l('Refund');
        $lang['info_refund_text'] = $this->l('The orderstatus will only change to `Refunded` when the full amount is refunded. Stock wont be updated.');
        $lang['info_log_title'] = $this->l('Logs');
        $lang['info_log_text'] = $this->l('For log information see `Advanced settings` and then `Logs`. Then filter on `Pay.`.');
        $lang['info_capture_title'] = $this->l('Capture');
        $lang['info_capture_text'] = $this->l('The order will be captured via Pay. and the customer will receive the invoice of the order from the payment method they ordered with.');
        $lang['info_capture_remaining_text'] = $this->l(
          'This order has already been partially captured, therefore you can only capture the remaining amount. The order will be captured via Pay. and the customer will receive the invoice of the order from the payment method they ordered with.'
        ); // phpcs:ignore
        return $lang;
    }

    /**
     * Update order status
     *
     * @param $orderId
     * @param $orderState
     * @param $cartId
     * @param $transactionId
     * @return void
     */
    public function updateOrderHistory($orderId, $orderState, $cartId = '', $transactionId = '')
    {
        $this->helper->payLog('updateOrderHistory', 'Update status. orderId: ' . $orderId . '. orderState: ' . $orderState, $cartId, $transactionId);
        $history = new OrderHistory();
        $history->id_order = $orderId;
        $history->changeIdOrderState($orderState, $orderId, true);
        $history->addWs();
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function createPaymentFeeProduct(): bool
    {
        $id_product = Configuration::get('PAYNL_FEE_PRODUCT_ID');
        $feeProduct = new Product(Configuration::get('PAYNL_FEE_PRODUCT_ID'), true);

        # Check if payment fee product exists
        if (!$id_product || !$feeProduct->id) {
            $objProduct = new Product();
            $objProduct->price = 0;
            $objProduct->is_virtual = 1;
            $objProduct->out_of_stock = 2;
            $objProduct->visibility = 'none';
            foreach (Language::getLanguages() as $language) {
                $objProduct->name[$language['id_lang']] = $this->l('Payment fee');
                $objProduct->link_rewrite[$language['id_lang']] = Tools::link_rewrite($objProduct->name[$language['id_lang']]);
            }

            if ($objProduct->add()) {
                //allow buy product out of stock
                StockAvailable::setProductDependsOnStock($objProduct->id, false);
                StockAvailable::setQuantity($objProduct->id, $objProduct->getDefaultIdProductAttribute(), 9999999);
                StockAvailable::setProductOutOfStock($objProduct->id, true);
                //update product id
                $id_product = $objProduct->id;
                Configuration::updateValue('PAYNL_FEE_PRODUCT_ID', $id_product);
            }
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function installOverrides()
    {
        // This version doesn't have overrides anymode, but prestashop still keeps them around.
        // By overriding this method we can prevent prestashop from reinstalling the old overrides
        return true;
    }

    /**
     * @return boolean
     */
    public function uninstall()
    {
        if (parent::uninstall()) {
            Configuration::deleteByName('PAYNL_FEE_PRODUCT_ID');
        }
        return true;
    }

    /**
     * @param array $params
     * @return void
     * @throws Exception
     */
    public function hookActionOrderSlipAdd(array $params)
    {
        if ($params['order']->module == 'paynlpaymentmethods') {
            try {
                $order = $params['order'];
                $productList = $params['productList'];
                $orderPayments = $order->getOrderPayments();
                $orderPayment = reset($orderPayments);
                if (!empty($orderPayment)) {
                    $refundAmount = 0;
                    foreach ($productList as $product) {
                        if (!empty($product['amount']) && $product['amount'] > 0) {
                            $refundAmount += $product['amount'];
                        }
                    }
                    $cancelProduct = Tools::getValue('cancel_product');
                    $partialRefundShipping = Tools::getValue('partialRefundShippingCost');
                    if (isset($cancelProduct['shipping']) && $cancelProduct['shipping'] === '1') {
                        $refundAmount += $order->total_shipping;
                    } elseif (isset($cancelProduct['shipping_amount']) && $cancelProduct['shipping_amount'] !== '0') {
                        $refundAmount += $cancelProduct['shipping_amount'];
                    } elseif ($partialRefundShipping && $partialRefundShipping !== '0') {
                        $refundAmount += $partialRefundShipping;
                    }

                    $currencyId = $orderPayment->id_currency;
                    $currency = new Currency($currencyId);
                    $strCurrency = $currency->iso_code;
                    $transactionId = $orderPayment->transaction_id ?? null;
                    if (!empty($refundAmount) && $refundAmount > 0) {
                        $payRefundRequest = new TransactionRefundRequest($transactionId, $refundAmount, $strCurrency);
                        $payRefundRequest->setConfig($this->helper->getConfig())->start();

                        $this->helper->payLog('Partial Refund', 'Partial Refund (' . $refundAmount . ') success ', $transactionId);
                        $this->get('session')->getFlashBag()->add('success', $this->l('Pay. successfully refunded ') . '(' . $refundAmount . ').');
                    } else {
                        $this->helper->payLog('Partial Refund', 'Partial Refund failed (refund amount is empty)', $transactionId);
                    }
                } else {
                    throw new Exception('Order has no Payments.');
                }
            } catch (\Throwable $e) {
                $this->helper->payLog('Partial Refund', 'Partial Refund failed (' . $e->getMessage() . ') ');
                $friendlyMessage = null;
                if ($e->getMessage() == 'PAY-14 - Refund too fast ') {
                    $friendlyMessage = $this->l('(Refunds can\'t be done in quick succession)');
                }
                $this->get('session')->getFlashBag()->add('error', $this->l('Pay. could not process partial refund, please check the status of your order in the Pay. admin. ') . $friendlyMessage);
            }
        }
    }

    /**
     * @param $params
     * @return array|false
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return false;
        }
        if (isset($params['cart']) && !$this->processingHelper->checkCurrency($params['cart'], $this)) {
            return false;
        }
        $cart = null;
        if (isset($params['cart'])) {
            $cart = $params['cart'];
        }

        return $this->paymentMethodsHelper->getPaymentMethodsForCheckout($this, $cart);
    }

    /**
     * @return string|null
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @return array
     */
    public function payTranslations(): array
    {
        $trans['advancedSettings'] = $this->l('Advanced settings');
        $trans['Version'] = $this->l('Version');
        $trans['accSettings'] = $this->l('Pay.');
        $trans['versionButton'] = $this->l('Check version');
        $trans['Status'] = $this->l('Status');
        $trans['tokenCode'] = $this->l('Token code');
        $trans['findTokenCode'] = $this->l('You can find your AT-code ');
        $trans['here'] = $this->l('here');
        $trans['signUp'] = $this->l(', not registered at Pay.? Sign up ');
        $trans['apiToken'] = $this->l('API token');
        $trans['findApiToken'] = $this->l('You can find your API token');
        $trans['salesLocation'] = $this->l('Sales location');
        $trans['findSalesLocation'] = $this->l('You can find the SL-code of your Sales location ');
        $trans['multicore'] = $this->l('Multicore');
        $trans['multicoreSettings'] = $this->l('Select the core to be used for processing payments');
        $trans['customMulticore'] = $this->l('Custom multicore');
        $trans['customMulticoreWarning'] = $this->l('Leave this empty unless Pay. advised otherwise');
        $trans['prefix'] = $this->l('Transaction description prefix');
        $trans['prefixSettings'] = $this->l('A prefix added to the transaction description');
        $trans['validationDelay'] = $this->l('Validation delay');
        $trans['validationDelaySettings'] = $this->l('When payment is done, wait for Pay.nl to validate payment before redirecting to success page');
        $trans['enabled'] = $this->l('Enabled');
        $trans['disabled'] = $this->l('Disabled');
        $trans['logging'] = $this->l('Pay. logging');
        $trans['sdkCaching'] = $this->l('SDK Caching');
        $trans['sdkCachingSettings'] = $this->l('Caches connection data to reduce API calls.');
        $trans['loggingSettings'] = $this->l('Log internal Pay. processing information.');
        $trans['testMode'] = $this->l('Test mode');
        $trans['testModeSettings'] = $this->l('Start transactions in sandbox mode for testing.');
        $trans['showImage'] = $this->l('Show images');
        $trans['showImageSetting'] = $this->l('Show the images of the payment methods in checkout.');
        $trans['payStyle'] = $this->l('Standard Pay. style');
        $trans['payStyleSettings'] = $this->l('Enable this if you want to use the standard Pay. style in the checkout');
        $trans['autoCapture'] = $this->l('Auto-capture');
        $trans['autoCaptureSettings'] = $this->l('Capture authorized transactions automatically when order is shipped.');
        $trans['autoVoid'] = $this->l('Auto-void');
        $trans['autoVoidSettings'] = $this->l('Void authorized transactions automatically when order is cancelled.');
        $trans['followPayment'] = $this->l('Follow payment method');
        $trans['followPaymentSettings'] = $this->l('This will ensure the order is updated with the actual payment method used to complete the order. This can differ from the payment method initially selected.');
        $trans['language'] = $this->l('Payment-screen language');
        $trans['languageSettings'] = $this->l('Select the language to show the payment screen in, automatic uses the browser preference');
        $trans['testIp'] = $this->l('Test IP address');
        $trans['testIpSettings'] = $this->l('Forces test-mode on these IP addresses. Separate IP\'s by comma\'s for multiple IP\'s. ');
        $trans['currentIp'] = $this->l('Current user IP address: ');
        $trans['suggestions'] = $this->l('Suggestions?');
        $trans['save'] = $this->l('Save');
        $trans['dutch'] = $this->l('Dutch');
        $trans['english'] = $this->l('English');
        $trans['spanish'] = $this->l('Spanish');
        $trans['italian'] = $this->l('Italian');
        $trans['french'] = $this->l('French');
        $trans['german'] = $this->l('German');
        $trans['webshopLanguage'] = $this->l('Webshop language');
        $trans['browserLanguage'] = $this->l('Automatic (Browser language)');
        $trans['selectPin'] = $this->l('Please select a pin-terminal');
        $trans['pin'] = $this->l('Pay safely via pin');
        $trans['payConnected'] = $this->l('Pay. successfully connected');
        $trans['custom'] = $this->l('Custom');

        return $trans;
    }

    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param $objPaymentMethod
     * @param $cartTotal
     * @return float|int
     */
    public function getPaymentFee($objPaymentMethod, $cartTotal)
    {
        $iFee = 0;
        if (isset($objPaymentMethod->fee_value)) {
            if (isset($objPaymentMethod->fee_percentage) && $objPaymentMethod->fee_percentage === true) {
                $iFee = (float)($cartTotal * $objPaymentMethod->fee_value / 100);
            } else {
                $iFee = (float)$objPaymentMethod->fee_value;
            }
        }

        return $iFee;
    }

    /**
     * @param $cart
     * @return mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function getCartTotalPrice($cart)
    {
        $summary = $cart->getSummaryDetails();
        $id_order = (int)Order::getIdByCartId($this->id);
        $order = new Order($id_order);
        if (Validate::isLoadedObject($order)) {
            $taxCalculationMethod = $order->getTaxCalculationMethod();
        } else {
            $taxCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
        }

        return $taxCalculationMethod == PS_TAX_EXC ? $summary['total_price_without_tax'] : $summary['total_price'];
    }

    /**
     * Determine new order state
     *
     * @param $payOrder
     * @return array
     */
    private function getNewOrderState($payOrder): array
    {
        $iOrderState = $this->statusPending;
        if ($payOrder->isPaid() || $payOrder->isAuthorized()) {
            $iOrderState = $this->statusPaid;
        } elseif ($payOrder->isCancelled()) {
            $iOrderState = $this->statusCanceled;
        }
        if ($payOrder->isRefundedFully()) {
            $iOrderState = $this->statusRefund;
        }

        try {
            $orderState = new OrderState($iOrderState);
        } catch (Exception $e) {
        }

        $orderStateName = $orderState->name ?? '';
        if (is_array($orderStateName)) {
            $orderStateName = array_pop($orderStateName);
        }

        return ['id' => $iOrderState, 'name' => $orderStateName];
    }

    /**
     * @param $transactionId
     * @param \PayNL\Sdk\Model\Pay\PayOrder $payOrder
     * @return ExchangeResponse
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    public function processPayment($transactionId, \PayNL\Sdk\Model\Pay\PayOrder $payOrder): ExchangeResponse
    {
        $message = '';
        $arrOrderState = $this->getNewOrderState($payOrder);

        $cartId = $payOrder->getReference();
        $this->helper->payLog('processPayment', 'orderStateName:' . $arrOrderState['name'] . '. iOrderState: ' . $arrOrderState['id'], $cartId, $transactionId);
        $orderId = Order::getIdByCartId($cartId);

        $profileId = $payOrder->getPaymentMethod();
        $paymentMethodName = PaymentMethod::getName($transactionId, $profileId);
        $cart = new Cart((int)$cartId);

        $amountPaid = $this->determineAmount($payOrder, $cart);

        if ($orderId) {
            # Order exists
            $order = new Order($orderId);
            $this->helper->payLog(
                'processPayment (order)',
                'orderStateName: ' . $arrOrderState['name'] . ' ' .
                'iOrderState: ' . $arrOrderState['id'] . '. ' .
                'orderRef: ' . $order->reference . ' ' .
                'orderModule:' . $order->module,
                $cartId,
                $transactionId
            );

            $saveOrder = false;

            # Check if the order is processed by Pay.
            if ($order->module !== 'paynlpaymentmethods') {
                return new ExchangeResponse(true, 'Not a Pay. order. Customer seemed to used different provider. Not updating the order.');
            }

            if ($payOrder->isRefundedPartial()) {
                return new ExchangeResponse(true, 'Partial refund received | OrderReference: ' . $order->reference);
            }

            if ($order->hasBeenPaid() && !$payOrder->isRefundedFully()) {
                return new ExchangeResponse(true, 'Ignoring ' . $payOrder->getStatusName() . '. Order is already paid | OrderReference: ' . $order->reference);
            }

            if (!$payOrder->isRefundedFully() && ($payOrder->isPaid() || $payOrder->isAuthorized())) {
                $orderPayment = null;
                $arrOrderPayment = OrderPayment::getByOrderReference($order->reference);
                foreach ($arrOrderPayment as $objOrderPayment) {
                    if ($objOrderPayment->transaction_id == $transactionId) {
                        $orderPayment = $objOrderPayment;
                    }
                }
                if (empty($orderPayment)) {
                    if (!$payOrder->isPaid()) {
                        return new ExchangeResponse(true, 'Ignoring not paid order');
                    }
                    $orderPayment = new OrderPayment();
                    $orderPayment->order_reference = $order->reference;
                }
                if (empty($orderPayment->payment_method)) {
                    $orderPayment->payment_method = $paymentMethodName;
                }
                if (empty($orderPayment->amount)) {
                    $orderPayment->amount = $amountPaid;
                }
                if (empty($orderPayment->transaction_id)) {
                    $orderPayment->transaction_id = $transactionId;
                }
                if (empty($orderPayment->id_currency)) {
                    $orderPayment->id_currency = $order->id_currency;
                }

                # In case of bank-transfer the total_paid_real isn't set, we're doing that now.
                if ($arrOrderState['id'] == $this->statusPaid && $order->total_paid_real == 0) {
                    $order->total_paid_real = $orderPayment->amount;
                    $saveOrder = true;
                }

                $dbTransaction = Transaction::get($transactionId);
                $dbTransactionId = $dbTransaction['payment_option_id'];
                if ($profileId != $dbTransactionId && Configuration::get('PAYNL_AUTO_FOLLOW_PAYMENT_METHOD')) {
                    Transaction::updatePaymentMethod($transactionId, $profileId);
                    $paymentOption = PaymentMethod::getName($transactionId, $profileId);

                    $order->payment = $paymentOption;
                    $orderPayment->payment_method = $paymentOption;

                    $saveOrder = true;
                    $this->helper->payLog('processPayment (follow payment method)', $transactionId . ' - When processing order: ' . $orderId . ' the original payment method id: ' . $dbTransactionId . ' was changed to: ' . $profileId); // phpcs:ignore
                }

                if ($saveOrder) {
                    $order->save();
                }
                $orderPayment->save();
            }

            $this->updateOrderHistory($order->id, $arrOrderState['id'], $cartId, $transactionId);
            $message = "Updated order (" . $order->reference . ") to: " . $arrOrderState['name'];
        } else {
            $iState = $payOrder->getStatusCode();

            if ($payOrder->isPaid() || $payOrder->isAuthorized() || $payOrder->isBeingVerified()) {
                try {
                    $currency_order = new Currency($cart->id_currency);

                    $this->helper->payLog(
                      'processPayment (paid)',
                      'orderStateName:' . $arrOrderState['name'] . '. iOrderState: ' . $arrOrderState['id'] . '. iState:' . $iState . '. CurrencyOrder: ' . $currency_order->iso_code . '. CartOrderTotal: ' . $cart->getOrderTotal(
                      ) . '. paymentMethodName: ' . $paymentMethodName . '. profileId: ' . $profileId . '. AmountPaid : ' . $amountPaid,
                      $cartId,
                      $transactionId
                    );

                    $this->validateOrder(
                      (int)$cartId,
                      $arrOrderState['id'],
                      $amountPaid,
                      $paymentMethodName,
                      null, array('transaction_id' => $transactionId),
                      null,
                      false,
                      $cart->secure_key
                    );

                    $orderId = Order::getIdByCartId($cartId);
                    $order = new Order($orderId);
                    $message = "Validated order (" . $order->reference . ") with status: " . $arrOrderState['name'];
                    $this->helper->payLog('processPayment', 'Order created. Amount: ' . $order->getTotalPaid(), $cartId, $transactionId);
                } catch (Exception $ex) {
                    $this->helper->payLog('processPayment', 'Could not validate(create) order.', $cartId, $transactionId);
                    throw new Exception('Could not validate order, error: ' . $ex->getMessage());
                }
            } else {
                if ($payOrder->isCancelled()) {
                    $message = "Status updated to CANCELED";
                }

                $this->helper->payLog(
                  'processPayment 3',
                  'OrderStateName:' . $arrOrderState['name'] . '. iOrderState: ' . $arrOrderState['id'] . '. iState:' . $iState,
                  $cartId,
                  $transactionId
                );
            }
        }

        return new ExchangeResponse(true, $message);
    }

    /**
     * @param \PayNL\Sdk\Model\Pay\PayOrder $payOrder
     * @param $cart
     * @return mixed|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function determineAmount(\PayNL\Sdk\Model\Pay\PayOrder $payOrder, $cart)
    {
        $cartTotalPrice = (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) ? $cart->getCartTotalPrice() : $this->getCartTotalPrice($cart);

        $arrPayAmounts = array($payOrder->getAmount());
        $amountPaid = in_array(round($cartTotalPrice, 2), $arrPayAmounts) ? $cartTotalPrice : null;

        if (is_null($amountPaid)) {
            if (in_array(round($cart->getOrderTotal(), 2), $arrPayAmounts)) {
                $amountPaid = $cart->getOrderTotal();
            } elseif (in_array(round($cart->getOrderTotal(false), 2), $arrPayAmounts)) {
                $amountPaid = $cart->getOrderTotal(false);
            }
        }
        $this->helper->payLog(
          'determineAmount',
          'getOrderTotal: ' . $cart->getOrderTotal() . ' ' . 'getOrderTotal(false): ' . $cart->getOrderTotal(false) . '. cartTotalPrice: ' . $cartTotalPrice . ' - ' . print_r(
            $arrPayAmounts,
            true
          ),
          $cart->id,
          $payOrder->getOrderId()
        ); // phpcs:ignore

        return $amountPaid;
    }

    /**
     * @param string $transactionId
     * @return \PayNL\Sdk\Model\Pay\PayOrder
     * @throws \PayNL\Sdk\Exception\PayException
     */
    public function getPayOrder(string $transactionId): \PayNL\Sdk\Model\Pay\PayOrder
    {
        $request = new PayNL\Sdk\Model\Request\OrderStatusRequest($transactionId);
        return $request->setConfig($this->helper->getConfig())->start();
    }

    /**
     * @param string $transactionId
     * @return \PayNL\Sdk\Model\Pay\PayOrder
     * @throws \PayNL\Sdk\Exception\PayException
     */
    public function getPayRefundOrder(string $transactionId): \PayNL\Sdk\Model\Pay\PayOrder
    {
        $request = new PayNL\Sdk\Model\Request\TransactionStatusRequest($transactionId);
        return $request->setConfig($this->helper->getConfig())->start();
    }

    /**
     * @param Cart $cart
     * @param $payment_option_id
     * @param array $parameters
     * @return string Result message
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws Exception
     */
    public function startPayment(Cart $cart, $payment_option_id, array $parameters = []): string
    {
        $request = new PayNL\Sdk\Model\Request\OrderCreateRequest();
        $request->setConfig($this->helper->getConfig());

        $currency = new Currency($cart->id_currency);
        /** @var CurrencyCore $currency */

        $objPaymentMethod = $this->getPaymentMethod($payment_option_id);

        # Make sure no fee is in the cart
        $cart->deleteProduct(Configuration::get('PAYNL_FEE_PRODUCT_ID'), 0);
        $cartTotal = $cart->getOrderTotal(true, Cart::BOTH, null, null, false);
        $iPaymentFee = $this->paymentMethodsHelper->getPaymentFee($objPaymentMethod, $cartTotal);
        $iPaymentFee = empty($iPaymentFee) ? 0 : $iPaymentFee;
        $cartId = $cart->id;

        try {
            $this->addPaymentFee($cart, $iPaymentFee);
        } catch (Exception $e) {
            $this->helper->payLog('startPayment', 'Could not add payment fee: ' . $e->getMessage(), $cartId);
        }

        $description = $cartId;

        if (Configuration::get('PAYNL_DESCRIPTION_PREFIX')) {
            $description = Configuration::get('PAYNL_DESCRIPTION_PREFIX') . $description;
        }

        $request->setServiceId(Tools::getValue('PAYNL_SERVICE_ID', Configuration::get('PAYNL_SERVICE_ID')));
        $request->setAmount($cart->getOrderTotal())->setCurrency($currency->iso_code);
        $request->setReturnurl($this->context->link->getModuleLink($this->name, 'finish', array(), true));
        $request->setExchangeUrl($this->context->link->getModuleLink($this->name, 'exchange', array(), true));
        $request->setPaymentMethodId($payment_option_id);

        $request->setTestmode(PayHelper::isTestMode());
        $request->setDescription($description);
        $request->setReference($cart->id);
        $request->setStats((new PayNL\Sdk\Model\Stats)->setExtra1($cart->id)->setObject($this->helper->getObjectInfo($this)));

        $requestOrderData = new PayNL\Sdk\Model\Order();

        $request->setCustomer($this->addressHelper->getCustomer($cart));

        $requestOrderData->setProducts($this->_getProductData($cart));

        $requestOrderData->setInvoiceAddress($this->addressHelper->getInvoiceAddress($cart));
        $requestOrderData->setDeliveryAddress($this->addressHelper->getDeliveryAddress($cart));

        $request->setOrder($requestOrderData);

        if (!empty($parameters['terminalCode'])) {
            $request->setTerminal($parameters['terminalCode']);
        }

        try {
            $payTransaction = $request->start();
        } catch (Exception $e) {
            $this->helper->payLog(
              'startPayment',
              'Starting new payment failed: ' . $cartTotal . '. Fee: ' . $iPaymentFee . ' Currency (cart): ' . $currency->iso_code . ' e:' . $e->getMessage(),
              $cartId
            );
            throw new Exception($e->getMessage(), $e->getCode());
        }

        $payTransactionId = $payTransaction->getOrderId();

        $this->helper->payLog(
          'startPayment',
          'Starting new payment with cart-total: ' . $cartTotal . '. Fee: ' . $iPaymentFee . ' Currency (cart): ' . $currency->iso_code,
          $cartId,
          $payTransactionId
        );

        Transaction::addTransaction($payTransactionId, $cart->id, $cart->id_customer, $payment_option_id, $cart->getOrderTotal());

        if ($this->shouldValidateOnStart($payment_option_id, $objPaymentMethod)) {
            $this->helper->payLog('startPayment', 'Pre-Creating order for pp : ' . $payment_option_id, $cartId, $payTransactionId);

            # Flush the package list, so the fee is added to it.
            $this->context->cart->getPackageList(true);

            $paymentMethodSettings = PaymentMethod::getPaymentMethodSettings($payment_option_id);
            $paymentMethodName = empty($paymentMethodSettings->name) ? 'Pay. Overboeking' : $paymentMethodSettings->name;

            $this->validateOrder($cart->id, $this->statusPending, 0, $paymentMethodName, null, array(), null, false, $cart->secure_key);

            $orderId = Order::getIdByCartId($cartId);
            $order = new Order($orderId);

            $orderPayment = new OrderPayment();
            $orderPayment->order_reference = $order->reference;
            $orderPayment->payment_method = $paymentMethodName;
            $orderPayment->amount = $cart->getOrderTotal();
            $orderPayment->transaction_id = $payTransactionId;
            $orderPayment->id_currency = $cart->id_currency;
            $orderPayment->save();
        } else {
            $this->helper->payLog('startPayment', 'Not pre-creating the order, waiting for payment.', $cartId, $payTransactionId);
        }

        return $payTransaction->getPaymentUrl();
    }


    /**
     * @return array|false
     */
    public function retrievePayMethods()
    {
        $paymentMethodsFromPay = [];
        try {
            $config = $this->helper->getConfig();
            $slCode = Tools::getValue('PAYNL_SERVICE_ID', Configuration::get('PAYNL_SERVICE_ID'));
            if (empty($slCode)) {
                throw new Exception('empty sl');
            }

            $request = new PayNL\Sdk\Model\Request\ServiceGetConfigRequest($slCode);
            $request->setConfig($config);
            $service = $request->start();

            foreach ($service->getPaymentMethods() as $method) {
                $paymentMethodsFromPay[$method->getId()] = $method;
            }

            Configuration::updateValue('PAYNL_CORES', json_encode(['dateSaved' => date('Y-m-d'), 'cores' => $service->getCores()]));
            Configuration::updateValue('PAYNL_TERMINALS', json_encode(['dateSaved' => date('Y-m-d'), 'terminals' => $service->getTerminals()]));
        } catch (Exception $e) {
            return false;
        }
        return $paymentMethodsFromPay;
    }

    /**
     * @return array|false
     */
    public function syncPaymentMethods($paymentMethodsFromPay)
    {
        # First retrieve local saved payment methods
        $localMethods = [];
        $savedPaymentMethods = json_decode(Configuration::get('PAYNL_PAYMENTMETHODS'), true);

        if (is_array($savedPaymentMethods)) {
            foreach ($savedPaymentMethods as $_savedMethod) {
                $localMethods[$_savedMethod['id']] = $_savedMethod;
            }
        }

        if ($paymentMethodsFromPay === false) {
            foreach ($localMethods as &$_method) {
                $_method = (object)$_method;
            }
            $this->avMethods = $localMethods;
            return false;
        }

        # When new method is retrieved, save to local methods
        foreach ($paymentMethodsFromPay as $methodId => $method) {
            if (!isset($localMethods[$methodId])) {
                # New method
                $localMethods[$methodId] = $this->paymentMethodsHelper->getNewMethod($method);
            } else {
                # Make sure null or empty fields are filled with pay data
                $localMethods[$methodId] = $this->updateMethod($localMethods[$methodId], $method);
            }
        }

        # Remove local method, when not found in retrieved collection
        if (!empty($paymentMethodsFromPay)) {
            foreach ($localMethods as $k => &$_locMethod) {
                if (!isset($paymentMethodsFromPay[$_locMethod['id']])) {
                    unset($localMethods[$k]);
                }
            }
        }

        # Parsing for smarty template
        foreach ($localMethods as &$v) {
            $v = (object)$v;
        }

        $this->avMethods = $localMethods;

        Configuration::updateValue('PAYNL_PAYMENTMETHODS', json_encode($localMethods));

        return ['method' => $localMethods, 'pay_methods' => $paymentMethodsFromPay];
    }

    /**
     * @param $localMethod
     * @param \PayNL\Sdk\Model\Method $method
     * @return mixed
     */
    public function updateMethod($localMethod, \PayNL\Sdk\Model\Method $method)
    {
        $arr = [
          'id' => $method->getId(),
          'name' => $method->getName(),
          'name_en' => $method->getName('en_GB'),
          'name_nl' => $method->getName('nl_NL'),
          'name_de' => $method->getName('de_DE'),
          'description' => $method->getDescription(),
          'description_en' =>  $method->getDescription('en_GB'),
          'description_nl' => $method->getDescription('en_GB'),
          'description_de' => $method->getDescription('de_DE'),
          'min_amount' => ($method->getMinAmount() / 100),
          'max_amount' => ($method->getMaxAmount() / 100),
          'image_path' => $method->getImage(),
          'bank_selection' => 'dropdown',
          'limit_carriers' => false,
          'allowed_carriers' => [],
          'create_order_on' => 'success'
        ];

        foreach ($arr as $fieldName => $methodValue) {
            if (!isset($localMethod[$fieldName])) {
                $localMethod[$fieldName] = $methodValue;
            } else {
                # So, payment methods exists already exists locally, make sure minimum amount is not lower than pay-minumum
                if ($fieldName == 'min_amount' && $localMethod[$fieldName] < ($method->getMinAmount() / 100)) {
                    $localMethod['min_amount'] = ($method->getMinAmount() / 100);
                }
            }
        }
        return $localMethod;
    }

    /**
     * @param $payment_option_id
     * @return mixed|null
     */
    public function getPaymentMethod($payment_option_id)
    {
        $paymentMethods = json_decode(Configuration::get('PAYNL_PAYMENTMETHODS'));

        foreach ($paymentMethods as $objPaymentOption) {
            if ($objPaymentOption->id == (int)$payment_option_id) {
                return $objPaymentOption;
            }
        }
        return null;
    }

    /**
     * @param Cart $cart
     * @param string $iFee_wt
     * @return void
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function addPaymentFee(Cart $cart, $iFee_wt)
    {
        if ($iFee_wt <= 0) {
            return;
        }
        $this->createPaymentFeeProduct();
        $feeProduct = new Product(Configuration::get('PAYNL_FEE_PRODUCT_ID'), true);
        $cart->updateQty(1, Configuration::get('PAYNL_FEE_PRODUCT_ID'));
        $cart->save();
        $vatRate = $feeProduct->tax_rate;
        # if product doesn't exist, it assumes to have a tax rate 0
        if ($vatRate == 0) {
            foreach ($cart->getProducts() as $product) {
                if ($vatRate < $product['rate']) {
                    $vatRate = $product['rate'];
                }
            }
        }

        $iFee_wt = (float)number_format($iFee_wt, 2);
        $iFee = (float)number_format((float)$iFee_wt / (1 + ($vatRate / 100)), 2);
        $specific_price = new SpecificPrice();
        $specific_price->id_product = (int)$feeProduct->id;
        # chosen product id
        $specific_price->id_product_attribute = $feeProduct->getDefaultAttribute($feeProduct->id);
        $specific_price->id_cart = (int)$cart->id;
        $specific_price->id_shop = (int)$this->context->shop->id;
        $specific_price->id_currency = 0;
        $specific_price->id_country = 0;
        $specific_price->id_group = 0;
        $specific_price->id_customer = 0;
        $specific_price->from_quantity = 1;
        $specific_price->price = (float)$iFee;
        $specific_price->reduction_type = 'amount';
        $specific_price->reduction_tax = 1;
        $specific_price->reduction = 0;
        $specific_price->from = date("Y-m-d H:i:s", strtotime('-1 day'));
        $specific_price->to = date("Y-m-d H:i:s", strtotime('+1 week'));
        $specific_price->add();
    }

    /**
     * @param Cart $cart
     * @return \PayNL\Sdk\Model\Products
     */
    private function _getProductData(Cart $cart)
    {
        $collectionProducts = new PayNL\Sdk\Model\Products();

        foreach ($cart->getProducts(true) as $product) {
            $collectionProducts->addProduct(
              new PayProduct(substr($product['id_product'], 0, 25), $product['name'], $product['price_wt'], null, PayProduct::TYPE_ARTICLE, $product['cart_quantity'], null, $product['rate'])
            );
        }

        $shippingCost_wt = $cart->getTotalShippingCost();
        $shippingCost = $cart->getTotalShippingCost(null, false);

        $vatClass = paynl_determine_vat_class($shippingCost_wt, ($shippingCost_wt - $shippingCost));

        $collectionProducts->addProduct(new PayProduct('shipping', $this->l('Shipping costs'), $shippingCost_wt, 'EUR', PayProduct::TYPE_SHIPPING, 1, $vatClass));

        $free_shipping_coupon_applied = false;
        $cartDetails = $cart->GetSummaryDetails();
        $discounts = (isset($cartDetails['discounts'])) ? $cartDetails['discounts'] : array();

        foreach ($discounts as $discount) {
            if ((!empty($discount['reduction_amount']) && $discount['reduction_amount'] > 0) || (!empty($discount['reduction_percent']) && $discount['reduction_percent'] > 0) || (!empty($discount['free_shipping']) && $discount['free_shipping'] === 1 && $free_shipping_coupon_applied === false)) {
                $discountValue = !empty($discount['value_real']) ? $discount['value_real'] : 0;
                $discountTax = !empty($discount['value_tax_exc']) ? $discount['value_tax_exc'] : 0;
                if ($discount['free_shipping'] === 1 && $free_shipping_coupon_applied === true) {
                    $discountValue -= $shippingCost_wt;
                    $discountTax -= $shippingCost;
                }
                if ($discountValue > 0) {
                    $vatClass = paynl_determine_vat_class($discountValue, $discountTax);
                    $desc = !empty($discount['description']) ? $discount['description'] : 'DISCOUNT';
                    $collectionProducts->addProduct(
                      new PayProduct(substr($discount['code'], 0, 25), $desc, -$discountValue, 'EUR', PayProduct::TYPE_DISCOUNT, 1, $vatClass)
                    );
                    if ($discount['free_shipping'] === 1) {
                        $free_shipping_coupon_applied = true;
                    }
                }
            }
        }

        return $collectionProducts;
    }

    /**
     * Retrieve language
     *
     * @param Cart $cart
     * @return mixed|string
     */
    private function getLanguageForOrder($cart)
    {
        $languageSetting = Tools::getValue('PAYNL_LANGUAGE', Configuration::get('PAYNL_LANGUAGE'));
        if ($languageSetting == 'auto') {
            return $this->getBrowserLanguage();
        } elseif ($languageSetting == 'cart') {
            return Language::getIsoById($cart->id_lang);
        } else {
            return $languageSetting;
        }
    }

    /**
     * @return string
     */
    private function getBrowserLanguage()
    {
        if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
            return $this->parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
        } else {
            return $this->parseDefaultLanguage(null);
        }
    }

    /**
     * @param string $http_accept
     * @param string $deflang
     * @return string
     */
    private function parseDefaultLanguage($http_accept, $deflang = "en")
    {
        if (isset($http_accept) && strlen($http_accept) > 1) {
            $lang = array();
            # Split possible languages into array
            $x = explode(",", $http_accept);
            foreach ($x as $val) {
                #check for q-value and create associative array. No q-value means 1 by rule
                if (preg_match(
                  "/(.*);q=([0-1]{0,1}.[0-9]{0,4})/i",
                  $val,
                  $matches
                )) {
                    $lang[$matches[1]] = (float)$matches[2] . '';
                } else {
                    $lang[$val] = 1.0;
                }
            }

            $arrLanguages = $this->getLanguages();
            $arrAvailableLanguages = array();
            foreach ($arrLanguages as $language) {
                if ($language['language_id'] != 'auto') {
                    $arrAvailableLanguages[] = $language['language_id'];
                }
            }

            #return default language (highest q-value)
            $qval = 0.0;
            foreach ($lang as $key => $value) {
                $languagecode = strtolower(substr($key, 0, 2));
                if (in_array($languagecode, $arrAvailableLanguages)) {
                    if ($value > $qval) {
                        $qval = (float)$value;
                        $deflang = $key;
                    }
                }
            }
        }

        return strtolower(substr($deflang, 0, 2));
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        return array(
          array(
            'language_id' => 'nl',
            'label' => $this->l('Dutch')
          ),
          array(
            'language_id' => 'en',
            'label' => $this->l('English')
          ),
          array(
            'language_id' => 'es',
            'label' => $this->l('Spanish')
          ),
          array(
            'language_id' => 'it',
            'label' => $this->l('Italian')
          ),
          array(
            'language_id' => 'fr',
            'label' => $this->l('French')
          ),
          array(
            'language_id' => 'de',
            'label' => $this->l('German')
          ),
          array(
            'language_id' => 'cart',
            'label' => $this->l('Webshop language')
          ),
          array(
            'language_id' => 'auto',
            'label' => $this->l('Automatic (Browser language)')
          ),
        );
    }

    /**
     * @param integer $payment_option_id
     * @param object $objPaymentMethod
     *
     * @return boolean
     */
    public function shouldValidateOnStart($payment_option_id, $objPaymentMethod)
    {
        if (($payment_option_id == PaymentMethod::METHOD_OVERBOEKING) || (isset($objPaymentMethod->create_order_on) && $objPaymentMethod->create_order_on == 'start')) {
            return true;
        }
        return false;
    }

    /**
     * This method handles the module's configuration page
     * @return string The page's HTML content
     */
    public function getContent()
    {
        $this->payConnection->setConnectionStatus(true);

        if (!class_exists('\PayNL\Sdk\Application\Application')) {
            $this->adminDisplayWarning($this->l('Cannot find Pay. SDK.'));
            return false;
        }
        $processingHtml = '';

        $payMethods = $this->retrievePayMethods();

        if (Tools::isSubmit('btnSubmit')) {
            $postErrors = [];

            if (!Tools::getValue('PAYNL_API_TOKEN') && empty(Configuration::get('PAYNL_API_TOKEN'))) {
                $postErrors[] = $this->l('API token is required.');
            }
            if (!Tools::getValue('PAYNL_SERVICE_ID')) {
                $postErrors[] = $this->l('Sales location is required');
            }
            if (!Tools::getValue('PAYNL_TOKEN_CODE')) {
                $postErrors[] = $this->l('Token code is required');
            }

            foreach ($postErrors as $err) {
                $this->adminDisplayWarning($err);
            }

            # Either way, save the settings...
            $processingHtml = $this->validateAndProcessSubmit($payMethods);
        }

        try {
            $arrResult = $this->syncPaymentMethods($payMethods);
            if (empty($arrResult)) {
                $this->payConnection->setConnectionStatus(false)->setConnectionErrorMessage('Failed to connect with Pay. - Please check your credentials.');
            }
        } catch (\Exception  $e) {
            $this->adminDisplayWarning($e->getMessage());
        }

        return $processingHtml . $this->renderAccountSettingsForm() . $this->renderPaymentMethodsForm() . $this->renderFeatureRequest();
    }

    /**
     * @return false|string
     */
    private function renderFeatureRequest()
    {
        $this->context->controller->addJs($this->_path . 'views/js/jquery-ui/jquery-ui.js');
        $this->context->controller->addCss($this->_path . 'css/admin.css');
        $this->smarty->assign(array(
          'ajaxURL' => $this->context->link->getModuleLink($this->name, 'ajax', array(), true),
        ));
        return $this->display(__FILE__, 'admin_featurerequest.tpl');
    }

    /**
     * @param array $payStandards
     * @return false|string
     */
    protected function validateAndProcessSubmit($payStandards = [])
    {
        if (Tools::isSubmit('btnSubmit'))
        {
            $savingMethods = json_decode(Tools::getValue('PAYNL_PAYMENTMETHODS'), true);

            foreach ($savingMethods as $saving_method) {
                if (!isset($payStandards[$saving_method['id']])) {continue;
                } else {
                    $method = $payStandards[$saving_method['id']];
                }
                /*
                if (($method->getMinAmount() / 100) > $saving_method['min_amount']) {
                    $x = $method->getName() . ' minimum amount must not be lower than ' . ($method->getMinAmount()/100) . ' . You requested: ' . $saving_method['min_amount'] . PHP_EOL;
                    $this->adminDisplayWarning($x);
                    return false;
                }*/
            }

            Configuration::updateValue('PAYNL_API_TOKEN', Tools::getValue('PAYNL_API_TOKEN'));
            Configuration::updateValue('PAYNL_SERVICE_ID', Tools::getValue('PAYNL_SERVICE_ID'));
            Configuration::updateValue('PAYNL_TOKEN_CODE', Tools::getValue('PAYNL_TOKEN_CODE'));
            Configuration::updateValue('PAYNL_TEST_MODE', Tools::getValue('PAYNL_TEST_MODE'));
            Configuration::updateValue('PAYNL_FAILOVER_GATEWAY', Tools::getValue('PAYNL_FAILOVER_GATEWAY'));
            Configuration::updateValue('PAYNL_CUSTOM_FAILOVER_GATEWAY', Tools::getValue('PAYNL_CUSTOM_FAILOVER_GATEWAY'));
            Configuration::updateValue('PAYNL_VALIDATION_DELAY', Tools::getValue('PAYNL_VALIDATION_DELAY'));
            Configuration::updateValue('PAYNL_PAYLOGGER', Tools::getValue('PAYNL_PAYLOGGER'));
            Configuration::updateValue('PAYNL_DESCRIPTION_PREFIX', Tools::getValue('PAYNL_DESCRIPTION_PREFIX'));
            Configuration::updateValue('PAYNL_CORE', Tools::getValue('PAYNL_CORE'));
            Configuration::updateValue('PAYNL_PAYMENTMETHODS', Tools::getValue('PAYNL_PAYMENTMETHODS'));
            Configuration::updateValue('PAYNL_LANGUAGE', Tools::getValue('PAYNL_LANGUAGE'));
            Configuration::updateValue('PAYNL_SHOW_IMAGE', Tools::getValue('PAYNL_SHOW_IMAGE'));
            Configuration::updateValue('PAYNL_STANDARD_STYLE', Tools::getValue('PAYNL_STANDARD_STYLE'));
            Configuration::updateValue('PAYNL_AUTO_CAPTURE', Tools::getValue('PAYNL_AUTO_CAPTURE'));
            Configuration::updateValue('PAYNL_TEST_IPADDRESS', Tools::getValue('PAYNL_TEST_IPADDRESS'));
            Configuration::updateValue('PAYNL_AUTO_VOID', Tools::getValue('PAYNL_AUTO_VOID'));
            Configuration::updateValue('PAYNL_AUTO_FOLLOW_PAYMENT_METHOD', Tools::getValue('PAYNL_AUTO_FOLLOW_PAYMENT_METHOD'));
            Configuration::updateValue('PAYNL_SDK_CACHING', Tools::getValue('PAYNL_SDK_CACHING'));
        }
        return $this->displayConfirmation($this->l('Settings updated'));
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function renderAccountSettingsForm()
    {
        $fields_form = $this->formHelper->getAccountFields($this);

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        if (property_exists($this, 'fields_form')) {
            $this->fields_form = array();
        }
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
          'fields_value' => $this->formHelper->getConfigFields($this),
          'languages' => $this->context->controller->getLanguages(),
          'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * @return string
     */
    public function renderPaymentMethodsForm()
    {
        $this->context->controller->addJs($this->_path . 'views/js/jquery-ui/jquery-ui.js');
        $this->context->controller->addCss($this->_path . 'css/admin.css');

        # Setting vars that might not exist after updating an old version.
        foreach ($this->avMethods as &$v) {
            if (!isset($v->create_order_on)) {
                $v->create_order_on = 'start';
            }
            if (!isset($v->image_path)) {
                $v->image_path = '';
            }
        }

        $this->smarty->assign(array(
          'available_countries' => $this->getCountries(),
          'available_carriers' => $this->getCarriers(),
          'image_url' => $this->_path . 'views/images/',
          'languages' => Language::getLanguages(true),
          'paymentmethods' => $this->avMethods,
          'showExternalLogoList' => [PaymentMethod::METHOD_GIVACARD],
          'showCreateOrderOnList' => [PaymentMethod::METHOD_PAYPAL]
        ));

        return $this->display(__FILE__, 'admin_paymentmethods.tpl');
    }

    /**
     * @return array
     */
    public function getCarriers()
    {
        return Carrier::getCarriers($this->context->language->id, true);
    }

    /**
     * @return array
     */
    public function getCountries()
    {
        return Country::getCountries($this->context->language->id, true);
    }
}
