<?php

namespace PaynlPaymentMethods\PrestaShop\Model;

/**
 *
 */
class PayConnection
{
    private $connectionErrorMessage;
    private bool $connectionStatus;

    public function __construct(bool $status = true, $errorMessage = '')
    {
        $this->connectionStatus = $status;
        $this->connectionErrorMessage = $errorMessage;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getConnectionStatus()
    {
        return $this->connectionStatus;
    }

    /**
     * @param $connectionStatus
     * @return $this
     */
    public function setConnectionStatus($connectionStatus)
    {
        $this->connectionStatus = $connectionStatus;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isConnected()
    {
        return $this->connectionStatus === true;
    }

    /**
     * @param mixed $connectionErrorMessage
     */
    public function setConnectionErrorMessage($connectionErrorMessage): void
    {
        $this->connectionErrorMessage = $connectionErrorMessage;
    }

    /**
     * @param $module
     * @return string
     */
    public function getHtmlTitle($module)
    {
        $mes = $this->connectionErrorMessage;
        if ($this->isConnected()) {
            $statusHTML = '<span class="value pay_connect_success">' . $module->l('Pay. successfully connected') . '</span>';
        } elseif (!empty($mes)) {
            $statusHTML = '<span class="value pay_connect_failure">' . $mes . '</span>';
        } else {
            $statusHTML = '<span class="value pay_connect_empty">' . $module->l('Pay. not connected') . '</span>';
        }

        return $statusHTML;
    }


}