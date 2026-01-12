<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use PaynlPaymentMethods\PrestaShop\PayHelper;
use Tools;

/**
 * Class LogoHelper
 *
 * @package PaynlPaymentMethods\PrestaShop\Helpers
 */
class LogoHelper
{
    const IMAGE_PATH = '/modules/paynlpaymentmethods/views/cache/images';
    private PayHelper $helper;

    public function __construct()
    {
        $this->helper = new PayHelper();
        return $this;
    }

    /**
     * @param $imagePath
     * @return string
     */
    public function getLogo($imagePath)
    {
        $path = LogoHelper::IMAGE_PATH . $imagePath;
        if (file_exists(_PS_ROOT_DIR_ . $path)) {
            return $path;
        } else {
            return 'https://static.pay.nl' . $imagePath;
        }
    }

    /**
     * Save logo
     *
     * @param $imagePath
     * @return void
     */
    public function saveLogo($imagePath): void
    {
        $path = _PS_ROOT_DIR_ . LogoHelper::IMAGE_PATH;
        if (file_exists($path . $imagePath) && (time() - filemtime($path . $imagePath) < 86400)) {
            return;
        }
        $imageUrl = 'https://static.pay.nl/' . $imagePath;
        $result = $this->downloadImage($imageUrl, $path, $imagePath);
        if(!$result) {
            $this->helper->payLog('downloadImage', sprintf('Could not download/save image. URL: %s | Path: %s | File: %s', $imageUrl, $path, $imagePath));
        }
    }

    /**
     * Download image from url
     *
     * @param string $url
     * @param string $basePath
     * @param string $image
     * @return bool
     */
    function downloadImage(string $url, string $basePath, string $image): bool
    {
        paydbg('downloadImage()');

        $data = Tools::file_get_contents($url);
        if ($data === false) {
            return false;
        }

        $fullPath = rtrim($basePath, '/') . '/' . ltrim($image, '/');

        $dir = dirname($fullPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        try {
            return file_put_contents($fullPath, $data) !== false;
        } catch (\Throwable $th) {
            $this->helper->payLog('downloadImage', sprintf('Could not save downloaded image %s. Exception: %s', $fullPath, $th->getMessage()));
            return false;
        }
    }

}