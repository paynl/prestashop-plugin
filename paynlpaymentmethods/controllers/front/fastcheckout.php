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

        // Check if a customer is already associated with the cart
        if (!Validate::isLoadedObject($context->customer)) {
            // Attempt to load guest customer from cart's id_customer
            $customer = new Customer($cart->id_customer);
            if (!Validate::isLoadedObject($customer) || !$customer->is_guest) {
                $customer = $this->createGuestCustomer();
            }
        } else {
            // Use the existing customer, assuming they are either a guest or will be treated as one for this process
            $customer = $context->customer;
        }

        $transaction = $this->module->startPayment($cart, 10, ['fastcheckout' => true]);

        Tools::redirect($transaction);
    }

    public function createGuestCustomer()
    {
        $context = Context::getContext();
        $cart = $context->cart;

        // Create a new guest customer object
        $customer = new Customer();
        $customer->is_guest = 1;

        $customer->firstname = 'Pay';
        $customer->lastname = 'Fastcheckout';
        $customer->email = 'fastcheckout_' . time() . '@pay.nl'; // Unique email required
        $customer->passwd = md5(time()); // Required, but not used for login
        $customer->save();
        $customer->add();

        $this->context->cart->id_address_delivery = 0;
        $this->context->cart->id_address_invoice = 0;
        $this->context->cart->save();

        $addressData = [
            'firstname' => 'Pay.',
            'lastname' => 'Fastcheckout',
            'company' => '',
            'address1' => '1 Street',
            'address2' => '',
            'postcode' => '1234 AB',
            'city' => 'Spijkenisse',
            'id_country' => (int) Country::getByIso('NL'), // Get ID from ISO code
            'phone' => '0123456789',
            'alias' => 'Fastcheckout Address', // Alias is required
        ];

        $address = new Address();
        $address->id_customer = (int) $customer->id;
        $address->id_manufacturer = 0;
        $address->id_supplier = 0;

        // Assign data to the Address object
        foreach ($addressData as $key => $value) {
            $address->{$key} = $value;
        }

        $address->save();

        $this->context->cart->id_address_delivery = $address->id;
        $this->context->cart->id_address_invoice = $address->id;

        $products = $this->context->cart->getProducts();
        foreach ($products as $product) {
            $this->context->cart->setProductAddressDelivery($product['id_product'], $product['id_product_attribute'], $product['id_address_delivery'], $address->id);
        }

        $this->context->cart->save();

        if (method_exists($this->context, 'updateCustomer')) {
            $this->context->updateCustomer($customer);
        } else {
            CustomerUpdater::updateContextCustomer($this->context, $customer);
        }

        return $customer;
    }
}