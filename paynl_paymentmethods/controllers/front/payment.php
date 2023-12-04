<?php

/*
 * 2007-2014 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2014 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5.0
 */
class paynl_paymentmethodsPaymentModuleFrontController extends ModuleFrontController
{

    public $ssl = true;
    public $display_column_left = false;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        /** @var CartCore $cart */
        $cart = $this->context->cart;

        $deliveryAddress = new Address((int)$cart->id_address_delivery);
        $invoiceAddress = new Address((int)$cart->id_address_invoice);

        $paymentOptionId = Tools::getValue('pid');
        $bank_id = Tools::getValue('bankid');

        $token = Configuration::get('PAYNL_TOKEN');
        $serviceId = Configuration::get('PAYNL_SERVICE_ID');

        $statusPending = Configuration::get('PAYNL_WAIT');

        if (!isset($cart->id)) {
            echo "Can't find cart";
            exit();
        }
        try {
            /** @var CurrencyCore $objCurrency */
            $objCurrency = Currency::getCurrencyInstance((int)$cart->id_currency);

            //validate the order
            $customer = new Customer($cart->id_customer);
            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $totalBase = Tools::convertPrice($total, $objCurrency, false);

            $orderStatus = Configuration::get('PAYNL_WAIT');
            $module = $this->module;

            $currencyId = $objCurrency->id;
            $currencyCode = $objCurrency->iso_code;

            $extraFeeBase = $module->getExtraCosts($paymentOptionId, $totalBase);
            $extraFee = Tools::convertPrice($extraFeeBase, $objCurrency, true);


            $total += $extraFee;

            $cartId = $cart->id;

            $apiStart = new Pay_Api_Start();

            //Klantgegevens meesturen
            /* array(
             *  initals
             *  lastName
             *  language
             *  accessCode
             *  gender (M or F)
             *  dob (DD-MM-YYYY)
             *  phoneNumber
             *  emailAddress
             *  bankAccount
             *  iban
             *  bic
             *  sendConfirmMail
             *  confirmMailTemplate
             *  address => array(
             *      streetName
             *      streetNumber
             *      zipCode
             *      city
             *      countryCode
             *  )
             *  invoiceAddress => array(
             *      initials
             *      lastname
             *      streetName
             *      streetNumber
             *      zipCode
             *      city
             *      countryCode
             *  )
             * )
             */
            $language = new Language($cart->id_lang);
            $arrEnduser = array();
            $arrEnduser['language'] = $language->iso_code;
            $arrEnduser['phoneNumber'] = (!empty($invoiceAddress->phone) ? $invoiceAddress->phone : $invoiceAddress->phone_mobile);
            $arrEnduser['initials'] = $customer->firstname;
            $arrEnduser['lastName'] = $customer->lastname;

            list($year, $month, $day) = explode('-', $customer->birthday);
            if(checkdate($month, $day, $year)){
                $arrEnduser['dob'] = $day . '-' . $month . '-' . $year;
            }

            $arrEnduser['emailAddress'] = $customer->email;
            $arrEnduser['gender'] = $customer->id_gender == 2 ? 'F' : 'M';

            // delivery address
            $arrAddress = array();
            $strAddress = $deliveryAddress->address1 . $deliveryAddress->address2;
            $arrStreetHouseNr = Pay_Helper::splitAddress($strAddress);
            $arrAddress['streetName'] = $arrStreetHouseNr[0];
            $arrAddress['streetNumber'] = !empty($arrStreetHouseNr[1]) ? substr($arrStreetHouseNr[1],0,44) : '';
            $arrAddress['zipCode'] = $deliveryAddress->postcode;
            $arrAddress['city'] = $deliveryAddress->city;
            $country = new Country($deliveryAddress->id_country);
            $arrAddress['countryCode'] = $country->iso_code;

            $arrEnduser['address'] = $arrAddress;

            // invoice address
            $arrAddress = array();
            $arrAddress['initials'] = $customer->firstname;
            $arrAddress['lastName'] = $customer->lastname;

            $strAddress = $invoiceAddress->address1 . $invoiceAddress->address2;
            $arrStreetHouseNr = Pay_Helper::splitAddress($strAddress);
            $arrAddress['streetName'] = $arrStreetHouseNr[0];
            $arrAddress['streetNumber'] = !empty($arrStreetHouseNr[1]) ? substr($arrStreetHouseNr[1],0,44) : '';
            $arrAddress['zipCode'] = $invoiceAddress->postcode;
            $arrAddress['city'] = $invoiceAddress->city;
            $country = new Country($invoiceAddress->id_country);
            $arrAddress['countryCode'] = $country->iso_code;

            $arrEnduser['invoiceAddress'] = $arrAddress;

            $arrEnduser['company']['name'] = $invoiceAddress->company;
            $arrEnduser['company']['countryCode'] = $country->iso_code;            
            $arrEnduser['company']['vatNumber'] = $invoiceAddress->vat_number;   

            $apiStart->setEnduser($arrEnduser);


            /**
             * @var $cart CartCore
             */
            $products = $cart->getProducts();
            foreach ($products as $product) {
                $taxClass = Pay_Helper::calculateTaxClass($product['price_wt'], $product['price_wt'] - $product['price']);
                $taxPercentage = $this->calculateTaxPercentage($product['price_wt'], $product['price']);
                $apiStart->addProduct($product['id_product'], $product['name'], round($product['price_wt'] * 100), $product['cart_quantity'], $taxClass, $taxPercentage);
            }

            //verzendkosten toevoegen
            $shippingCost = $cart->getTotalShippingCost(null,true);
            $shippingCost_no_tax = $cart->getTotalShippingCost(null,false);
            if ($shippingCost != 0) {
                $taxClass= Pay_Helper::calculateTaxClass($shippingCost, $shippingCost-$shippingCost_no_tax);
                $taxPercentage = $this->calculateTaxPercentage($shippingCost, $shippingCost_no_tax);
                $apiStart->addProduct('SHIPPING', 'Verzendkosten', round($shippingCost * 100), 1, $taxClass, $taxPercentage);
            }

            //Inpakservice toevoegen
            if ($cart->gift != 0) {
                $packingCost = $cart->getGiftWrappingPrice(true);
                $packingCost_no_tax = $cart->getGiftWrappingPrice(false);
                if ($packingCost != 0) {
                    $taxClass = Pay_Helper::calculateTaxClass($packingCost, $packingCost-$packingCost_no_tax);
                    $taxPercentage = $this->calculateTaxPercentage($packingCost, $packingCost_no_tax);
                    $apiStart->addProduct('PACKING', 'Inpakservice', round($packingCost * 100), 1, $taxClass, $taxPercentage);
                }
            }

            $cartRules = $cart->getCartRules();

            foreach ($cartRules as $cartRule) {
                $apiStart->addProduct('DISCOUNT' . $cartRule['id_cart_rule'], $cartRule['description'], round($cartRule['value_real'] * -100), 1, 'H');
            }

            if ($extraFee != 0) {
                $vatRate = $this->module->getHighestVatRate($cart);
                $vatClass = Pay_Helper::nearestTaxClass($vatRate);
                $apiStart->addProduct('PAYMENTFEE', 'Betaalkosten', round($extraFee * 100), 1, $vatClass, $vatRate);
            }


            $apiStart->setApiToken($token);
            $apiStart->setServiceId($serviceId);

            $description = Configuration::get('PAYNL_DESCRIPTION_PREFIX') . ' ' . $cart->id;
            $description = trim($description);
            $apiStart->setDescription($description);
            $apiStart->setExtra1('CartId: ' . $cart->id);
            $apiStart->setObject('prestashop16 ' . $module->getVersion());
            $apiStart->setOrderNumber($cart->id);
            if ($bank_id) {
                $apiStart->setPaymentOptionSubId($bank_id);
            }

            $apiStart->setPaymentOptionId($paymentOptionId);

            $finishUrl = Context::getContext()->link->getModuleLink('paynl_paymentmethods', 'return');
            $exchangeUrl = Context::getContext()->link->getModuleLink('paynl_paymentmethods', 'exchange');

            $apiStart->setFinishUrl($finishUrl);
            $apiStart->setExchangeUrl($exchangeUrl);
            $apiStart->setAmount(round($total * 100));
            $apiStart->setCurrency($currencyCode);

            $result = $apiStart->doRequest();

            $startData = $apiStart->getPostData();

            Pay_Helper_Transaction::addTransaction($result['transaction']['transactionId'], $paymentOptionId, round($total * 100), $currencyCode, $cartId, $startData);

            if ($this->module->validateOnStart($paymentOptionId)) {
                Pay_Helper::payLog('Payment start: (pre)validateOrderPay. Amount: '. $total, $result['transaction']['transactionId'], $cartId);
                $module->validateOrderPay((int)$cart->id, $statusPending, $total, $extraFee, $module->getPaymentMethodName($paymentOptionId), NULL, array('transaction_id' => $result['transaction']['transactionId']), (int)$currencyId, false, $customer->secure_key);
            }
            else {
                Pay_Helper::payLog('Payment initiated. Amount: ' . $total . '. Extrafee: ' . $extraFee, $result['transaction']['transactionId'], $cartId);
            }

            Tools::redirect($result['transaction']['paymentURL']);

        } catch (Exception $e) {
            $this->context->cookie->__set('redirect_errors', $e->getMessage());
            Tools::redirect(Context::getContext()->link->getModuleLink('paynl_paymentmethods', 'notification'));
        }
        //betaling starten
    }

    public function calculateTaxPercentage($amountInclTax, $amountexclTax)
    {
        $taxAmount = $amountInclTax - $amountexclTax;
        if ($taxAmount == 0 || $amountInclTax == 0) {
            return 0;
        }
        $amountExclTax = $amountInclTax - $taxAmount;
        if ($amountExclTax == 0) {
            return 100;
        }

        return $this->roundDown(($taxAmount / $amountExclTax) * 100, 2);
    }

    public function roundDown($decimal, $precision)
    {
        $sign = $decimal > 0 ? 1 : -1;
        $base = pow(10, $precision);
        return floor(abs($decimal) * $base) / $base * $sign;
    }
}
