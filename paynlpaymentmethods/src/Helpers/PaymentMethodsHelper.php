<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use Language;
use PaynlPaymentMethods\PrestaShop\PayHelper;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Tools;
use Configuration;
use Address;

/**
 * Class PaymentMethodsHelper
 *
 * @package PaynlPaymentMethods\PrestaShop\Helpers
 */
class PaymentMethodsHelper
{
    private $helper;
    private $formHelper;

    public function __construct()
    {
        $this->helper = new PayHelper();
        $this->formHelper = new FormHelper();
        return $this;
    }

    /**
     * For retrieving payment methods in checkout
     *
     * @param $module
     * @param $cart
     * @return array
     */
    public function getPaymentMethodsForCheckout($module, $cart = null): array
    {
        global $cookie;

        $iso_code = Language::getIsoById((int)$cookie->id_lang);
        $availablePaymentMethods = json_decode(Configuration::get('PAYNL_PAYMENTMETHODS'));
        $cartTotal = $cart->getOrderTotal();

        $path = $module->getPath();
        $action = $module->getContext()->link->getModuleLink($module->name, 'startPayment', array(), true);

        $bShowLogo = Configuration::get('PAYNL_SHOW_IMAGE');
        $paymentMethods = [];
        foreach ($availablePaymentMethods as $paymentMethod) {
            $paymentMethod->fee = $this->getPaymentFee($paymentMethod, $cartTotal);

            if (!$this->isPaymentMethodAvailable($cart, $paymentMethod, $paymentMethod->fee, $cartTotal)) {
                continue;
            }

            $objPaymentMethod = new PaymentOption();
            $name = $paymentMethod->name;

            # If a translation is found, then use that name field:
            if (!empty($paymentMethod->{'name_' . $iso_code}) && count(Language::getLanguages(true)) > 1) {
                $name = $paymentMethod->{'name_' . $iso_code};
            }
            if ($paymentMethod->fee > 0) {
                $name .= " (+ " . Tools::displayPrice($paymentMethod->fee, (int)$cart->id_currency, true) . ")";
            }

            $objPaymentMethod->setCallToActionText($name)
                ->setAction($action)
                ->setInputs([
                    'payment_option_id' => [
                        'name' => 'payment_option_id',
                        'type' => 'hidden',
                        'value' => $paymentMethod->id,
                    ],
                ]);

            $imagePath = $paymentMethod->image_path ?? '';
            if ($bShowLogo && !empty($imagePath)) {
                $objPaymentMethod->setLogo($path . 'views/images/' . $imagePath);
                if (!empty($paymentMethod->external_logo)) {
                    $objPaymentMethod->setLogo($paymentMethod->external_logo);
                }
            }

            $strDescription = empty($paymentMethod->description) ? null : $paymentMethod->description;
            if (!empty($paymentMethod->{'description_' . $iso_code}) && count(Language::getLanguages(true)) > 1) {
                $strDescription = $paymentMethod->{'description_' . $iso_code};
            }

            try {
                $payForm = $this->formHelper->getPayForm($module, $paymentMethod->id, $strDescription, $bShowLogo);
            } catch (Exception $e) {
            }

            if (!empty($payForm)) {
                $objPaymentMethod->setForm($payForm);
            }
            $objPaymentMethod->setModuleName('paynl');
            $paymentMethods[] = $objPaymentMethod;
        }
        return $paymentMethods;
    }

    /**
     * @param $cart
     * @param $paymentMethod
     * @param $paymentFee
     * @param $cartTotal
     * @return bool
     */
    public function isPaymentMethodAvailable($cart, $paymentMethod, $paymentFee, $cartTotal = null): bool
    {
        if (is_null($cartTotal)) {
            $cartTotal = $cart->getOrderTotal(true, Cart::BOTH);
        }

        if (!isset($paymentMethod->enabled) || !$paymentMethod->enabled) {
            return false;
        }

        $totalWithFee = $cartTotal + $paymentFee;

        # Check min and max amount
        if (!empty($paymentMethod->min_amount) && $totalWithFee < $paymentMethod->min_amount) {
            return false;
        }
        if (!empty($paymentMethod->max_amount) && $totalWithFee > $paymentMethod->max_amount) {
            return false;
        }

        # Check country
        if (isset($paymentMethod->limit_countries) && $paymentMethod->limit_countries == 1) {
            $address = new Address($cart->id_address_delivery);
            $address->id_country;
            $allowed_countries = $paymentMethod->allowed_countries;
            if (!in_array($address->id_country, $allowed_countries)) {
                return false;
            }
        }

        # Check carriers
        if (isset($paymentMethod->limit_carriers) && $paymentMethod->limit_carriers == 1) {
            $allowed_carriers = $paymentMethod->allowed_carriers;
            if (!in_array($cart->id_carrier, $allowed_carriers)) {
                return false;
            }
        }

        # Check customer type
        $invoiceAddressId = $cart->id_address_invoice;
        $objInvoiceAddress = new Address($invoiceAddressId);

        if (isset($objInvoiceAddress->company) && isset($paymentMethod->customer_type)) {
            if (!empty(trim($objInvoiceAddress->company)) && $paymentMethod->customer_type == 'private') {
                return false;
            }
            if (empty(trim($objInvoiceAddress->company)) && $paymentMethod->customer_type == 'business') {
                return false;
            }
        }

        return true;
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
            if (isset($objPaymentMethod->fee_percentage) && $objPaymentMethod->fee_percentage == true) {
                $iFee = (float)($cartTotal * $objPaymentMethod->fee_value / 100);
            } else {
                $iFee = (float)$objPaymentMethod->fee_value;
            }
        }
        return $iFee;
    }

    /**
     * @param \PayNL\Sdk\Model\Method $method
     * @return array
     */
    public function getNewMethod(\PayNL\Sdk\Model\Method $method)
    {
        return [
            'id' => $method->getId(),
            'brand_id' => '',
            'name' => $method->getName(),
            'name_en' => $method->getName('en_GB'),
            'name_nl' => $method->getName('nl_NL'),
            'name_de' => $method->getName('de_DE'),
            'description' => $method->getDescription(),
            'description_en' => $method->getDescription('en_GB'),
            'description_nl' => $method->getDescription('en_GB'),
            'description_de' => $method->getDescription('de_DE'),
            'min_amount' => ($method->getMinAmount() / 100),
            'max_amount' => ($method->getMaxAmount() / 100),
            'fee_value' => 0,
            'customer_type' => 'both',
            'enabled' => 0,
            'limit_countries' => '',
            'limit_carriers' => false,
            'fee_percentage' => '',
            'image_path' => $method->getImage(),
            'allowed_countries' => [],
            'allowed_carriers' => [],
            'external_logo' => '',
            'create_order_on' => 'success',
            'bank_selection' => 'dropdown'
        ];
    }

}