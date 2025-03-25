<?php

namespace PaynlPaymentMethods\PrestaShop;

use Configuration;

class PaymentMethod
{
    const METHOD_SANDBOX = 613; // phpcs:ignore
    const METHOD_OVERBOEKING = 136; // phpcs:ignore
    const METHOD_IDEAL = 10; // phpcs:ignore
    const METHOD_SOFORT = 556; // phpcs:ignore
    const METHOD_INSTORE = 1729; // phpcs:ignore
    const METHOD_PIN = 1927; // phpcs:ignore
    const METHOD_INSTORE_PROFILE_ID = 1633; // phpcs:ignore
    const METHOD_GIVACARD = 1657; // phpcs:ignore
    const METHOD_PAYPAL = 138;  // phpcs:ignore

    /**
     * @param string $transactionId
     * @param string $profileId
     * @return string
     */
    public static function getName($transactionId = null, $profileId = null)
    {
        if ($profileId == self::METHOD_SANDBOX) {
            $paymentMethodName = 'Sandbox';
        } else {
            $dbTransaction = Transaction::get($transactionId);
            if (!empty($dbTransaction['payment_option_id'])) {
                $settings = self::getPaymentMethodSettings($dbTransaction['payment_option_id']);
            } else {
                $settings = self::getPaymentMethodSettings($profileId);
            }
            $paymentMethodName = empty($settings->name) ? '' : $settings->name;
        }
        if (empty(trim($paymentMethodName))) {
            $paymentMethodName = 'Pay.';
        }
        return $paymentMethodName;
    }

    /**
     * Retrieve the settings of a specific payment with payment_profile_id
     *
     * @param string $payment_profile_id
     * @return boolean
     */
    public static function getPaymentMethodSettings($payment_profile_id)
    {
        $paymentMethods = json_decode(Configuration::get('PAYNL_PAYMENTMETHODS'));
        foreach ($paymentMethods as $objPaymentSettings) {
            if ($objPaymentSettings->id == $payment_profile_id) {
                return $objPaymentSettings;
            }
        }
        return false;
    }

    /**
     * Show errors
     *
     * @param string $error
     * @param string $object
     * @return void
     */
    public static function paymentError($error, $object)
    {
        $object->errors[] = $error;
        $object->redirectWithNotifications('index.php?controller=order&step=1');
    }
}
