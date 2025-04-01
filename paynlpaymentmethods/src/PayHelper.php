<?php

namespace PaynlPaymentMethods\PrestaShop;
use PayNL\Sdk\Config\Config as PayConfig;
use Tools;
use Configuration;
use PrestaShopLogger;

class PayHelper
{
    private $payLogEnabled = null;

    /**
     * @return false|string
     */
    private function getCore()
    {
        $core = Configuration::get('PAYNL_FAILOVER_GATEWAY');
        if ($core == 'custom') {
            $core = Configuration::get('PAYNL_CUSTOM_FAILOVER_GATEWAY');
        }
        return $core;
    }

    /**
     * @return PayConfig
     */
    public function getConfig() : PayConfig
    {
        $config = new PayConfig();

        $config->setCaching(Configuration::get('PAYNL_SDK_CACHING', Configuration::get('PAYNL_SDK_CACHING')));

        var_dump($config->isCacheEnabled());
        $config->setUsername(Tools::getValue('PAYNL_TOKEN_CODE', Configuration::get('PAYNL_TOKEN_CODE')));
        $config->setPassword(Tools::getValue('PAYNL_API_TOKEN', Configuration::get('PAYNL_API_TOKEN')));
        $config->setCore($this->getCore());
        #$serviceId = Tools::getValue('PAYNL_SERVICE_ID', Configuration::get('PAYNL_SERVICE_ID'));
        return $config;
    }

    /**
     * @return boolean
     */
    public static function isTestMode()
    {
        $ip = Tools::getRemoteAddr();
        $ipconfig = Configuration::get('PAYNL_TEST_IPADDRESS');
        if (!empty($ipconfig)) {
            $allowed_ips = explode(',', $ipconfig);
            if (
              in_array($ip, $allowed_ips) &&
              filter_var($ip, FILTER_VALIDATE_IP) &&
              strlen($ip) > 0 &&
              count($allowed_ips) > 0
            ) {
                return true;
            }
        }
        return Configuration::get('PAYNL_TEST_MODE');
    }


    /**
     * @param $method
     * @param $message
     * @param null $cartid
     * @param null $transactionId
     */
    public function payLog($method, $message, $cartid = null, $transactionId = null)
    {
      if ($this->payLogEnabled === null) {
        $this->payLogEnabled = Configuration::get('PAYNL_PAYLOGGER') == 1;
      }

        if ($this->payLogEnabled) {
            $strCartId = empty($cartid) ? '' : ' CartId: ' . $cartid;
            $strTransaction = empty($transactionId) ? '' : ' [ ' . $transactionId . ' ] ';

            if (is_array($message)) {
                $message = print_r($message, true);
            }

            PrestaShopLogger::addLog('Pay. - ' . $method . ' - ' . $strTransaction . $strCartId . ': ' . $message);

            if(function_exists('displayPayDebug'))  {
                displayPayDebug('Pay. - ' . $method . ' - ' . $strTransaction . $strCartId . ': ' . $message);
            }
        }
    }

    /**
     * @return false|string
     */
    public function getObjectInfo($module)
    {
        $object_string = 'prestashop ';
        $object_string .= !empty($module->version) ? $module->version : '-';
        $object_string .= ' | ';
        $object_string .= defined('_PS_VERSION_') ? _PS_VERSION_ : '-';
        $object_string .= ' | ';
        $object_string .= substr(phpversion(), 0, 3);

        return substr($object_string, 0, 64);
    }

}