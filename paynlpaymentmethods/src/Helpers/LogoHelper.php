<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use PaynlPaymentMethods\PrestaShop\PayHelper;
use Language;
use Tools;
use Configuration;
use Address;

/**
 * Class PaymentMethodsHelper
 *
 * @package PaynlPaymentMethods\PrestaShop\Helpers
 */
class LogoHelper
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
     * Save logo
     *
     * @param $path
     * @param $imagePath   
     */
    public function getLogo($imagePath)
    {
        $path = '/modules/paynlpaymentmethods/views/cache/images' . $imagePath;
        if (file_exists(_PS_ROOT_DIR_ . $path)) {
            return $path;
        } else {
            return 'https://static.pay.nl' . $imagePath;
        }
    }

    /**
     * Save logo
     *
     * @param $path
     * @param $imagePath   
     */
    public function saveLogo($imagePath)
    {
        $path = _PS_ROOT_DIR_ . '/modules/paynlpaymentmethods/views/cache/images';
        if (file_exists($path . $imagePath) && (time() - filemtime($path . $imagePath) < 86400)) {
            return;
        }
        $imageUrl = 'https://static.pay.nl/' . $imagePath;
        $result = $this->downloadImage($imageUrl, $path, $imagePath);
    }

    /**
     * Download image from url
     *
     * @param string $url
     * @param string $path
     * @param string $imagePath
     * @return bool
     */
    function downloadImage($url, $path, $image)
    {
        $data = Tools::file_get_contents($url);
        if ($data !== false) {
            try {
                $imagePath = explode('/', $image)[1];
                if (!is_dir($path . '/' . $imagePath . '/')) {
                    mkdir($path . '/' . $imagePath . '/', 0755, true);
                }
                $result = file_put_contents($path . $image, $data);
            } catch (\Throwable $th) {
                return false;
            }
            return $result;
        }
        return false;
    }
}