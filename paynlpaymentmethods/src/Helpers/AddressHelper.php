<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use Address;
use Country;
use Language;
use Tools;
use Customer;
use Cart;
use Configuration;
/**
 * Class AddressHelper
 *
 * @package PaynlPaymentMethods\PrestaShop\Helpers
 */
class AddressHelper
{
    public function __construct()
    {
        return $this;
    }

    private $module = null;

    /**
     * @param Cart $cart
     * @param $module
     * @return \PayNL\Sdk\Model\Customer
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function getCustomer(Cart $cart, $module)
    {
        $this->module = $module;
        $shippingAddressId = $cart->id_address_delivery;
        $objInvoiceAddress = new Address($cart->id_address_invoice);
        $objShippingAddress = new Address($shippingAddressId);
        $cartCustomer = new Customer($cart->id_customer);
        $invoiceCountry = new Country($objInvoiceAddress->id_country);

        $customer = new \PayNL\Sdk\Model\Customer();
        $customer->setFirstName($objShippingAddress->firstname);
        $customer->setLastName($objShippingAddress->lastname);

        $bd = $this->getDOB($cartCustomer->birthday);
        if(!empty($bd)) {
            $customer->setBirthDate($this->getDOB($cartCustomer->birthday));
        }
        $customer->setGender($cartCustomer->id_gender == 1 ? 'M' : ($cartCustomer->id_gender == 2 ? 'F' : ''));
        $customer->setPhone($objShippingAddress->phone ? $objShippingAddress->phone : $objShippingAddress->phone_mobile);
        $customer->setEmail($cartCustomer->email);

        $lang = $this->getLanguageForOrder($cart);
        $country = new Country((int) $objInvoiceAddress->id_country);

        $langIso = strtolower(substr($lang ?: 'nl', 0, 2));
        $countryIso = strtoupper($country->iso_code ?: 'NL');

        $customer->setLocale($langIso . '_' . $countryIso);

        $company = new \PayNL\Sdk\Model\Company();
        $company->setName($objInvoiceAddress->company);
        $company->setVat($objInvoiceAddress->vat_number);
        $company->setCountryCode($invoiceCountry->iso_code);

        $customer->setCompany($company);

        return $customer;
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
    private function getBrowserLanguage(): string
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
    private function parseDefaultLanguage($http_accept, $deflang = "en"): string
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
    public function getLanguages(): array
    {
        return array(
            array(
                'language_id' => 'nl',
                'label' => $this->module->l('Dutch')
            ),
            array(
                'language_id' => 'en',
                'label' => $this->module->l('English')
            ),
            array(
                'language_id' => 'es',
                'label' => $this->module->l('Spanish')
            ),
            array(
                'language_id' => 'it',
                'label' => $this->module->l('Italian')
            ),
            array(
                'language_id' => 'fr',
                'label' => $this->module->l('French')
            ),
            array(
                'language_id' => 'de',
                'label' => $this->module->l('German')
            ),
            array(
                'language_id' => 'cart',
                'label' => $this->module->l('Webshop language')
            ),
            array(
                'language_id' => 'auto',
                'label' => $this->module->l('Automatic (Browser language)')
            ),
        );
    }


    /**
     * @param $dob
     * @return string|null
     */
    public function getDOB($dob)
    {
        if (empty(trim($dob))) {
            return null;
        } elseif ($dob == '00-00-0000' || $dob == '0000-00-00') {
            return null;
        }
        return $dob;
    }


    /**
     * @param Cart $cart
     * @return \PayNL\Sdk\Model\Address
     */
    public function getInvoiceAddress(Cart $cart)
    {
        $invoiceAddressId = $cart->id_address_invoice;
        $objInvoiceAddress = new Address($invoiceAddressId);

        $arrStreet = paynl_split_address(trim($objInvoiceAddress->address1 . ' ' . $objInvoiceAddress->address2));

        $invoiceCountry = new Country($objInvoiceAddress->id_country);

        $invAddress = new \PayNL\Sdk\Model\Address();
        $invAddress->setStreetName($arrStreet['street']);
        $invAddress->setStreetNumber($arrStreet['number']);
        $invAddress->setZipCode($objInvoiceAddress->postcode);
        $invAddress->setCity($objInvoiceAddress->city);
        $invAddress->setCountryCode($invoiceCountry->iso_code);

        return $invAddress;
    }


    /**
     * @param Cart $cart
     * @return \PayNL\Sdk\Model\Address
     */
    public function getDeliveryAddress(Cart $cart)
    {
        $objShippingAddress = new Address($cart->id_address_delivery);
        $arrStreet = paynl_split_address(trim($objShippingAddress->address1 . ' ' . $objShippingAddress->address2));

        $shipCountry = new Country($objShippingAddress->id_country);

        $shippingAddress = new \PayNL\Sdk\Model\Address();
        $shippingAddress->setStreetName($arrStreet['street']);
        $shippingAddress->setStreetNumber($arrStreet['number']);
        $shippingAddress->setZipCode($objShippingAddress->postcode);
        $shippingAddress->setCity($objShippingAddress->city);
        $shippingAddress->setCountryCode($shipCountry->iso_code);

        return $shippingAddress;
    }

}