<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use Address;
use Country;
use Customer;
use Cart;

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

    /**
     * @param Cart $cart
     * @return \PayNL\Sdk\Model\Customer
     */
    public function getCustomer(Cart $cart)
    {
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

        $company = new \PayNL\Sdk\Model\Company();
        $company->setName($objInvoiceAddress->company);
        $company->setVat($objInvoiceAddress->vat_number);
        $company->setCountryCode($invoiceCountry->iso_code);

        $customer->setCompany($company);

        return $customer;
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