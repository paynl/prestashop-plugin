<?php

namespace PaynlPaymentMethods\PrestaShop\Helpers;

use PayNL\Sdk\Model\Request\TerminalsBrowseRequest;
use PaynlPaymentMethods\PrestaShop\PaymentMethod;
use PaynlPaymentMethods\PrestaShop\PayHelper;
use Tools;
use Configuration;

/**
 * Class FormHelper
 *
 * @package PaynlPaymentMethods\PrestaShop\Helpers
 */
class FormHelper
{
    public function __construct()
    {
        return $this;
    }

    /**
     * @param $module
     * @return array
     */
    private function getCores($module): array
    {
        $l = $module->payTranslations();

        $savedCores = json_decode(Configuration::get('PAYNL_CORES'), true);

        if (!isset($savedCores['cores'])) {
            $savedCores['cores'] = [];
        }

        $cores = is_array($savedCores['cores']) ? $savedCores['cores'] : [];
        if (!empty($cores)) {
            # If config is not okay, and therefor no core are available, dont add custom as only multicore option.
            $cores[] = ['domain' => 'custom', 'label' => $l['Custom'] ?? 'Custom'];
        }

        return $cores;
    }


    /**
     * @param $module
     * @return array
     */
    public function getAccountFields($module)
    {
        $l = $module->payTranslations();

        return array(
          'form' => array(
            'legend' => array(
              'title' => sprintf($l['accSettings'], $module->version),
              'icon' => 'icon-envelope'
            ),
            'input' => array(
              array(
                'type' => '',
                'label' => $l['Version'],
                'name' => 'PAYNL_VERSION',
                'desc' => '<span class="version-check"><span id="pay-version-check-current-version">' . $module->version . '</span><span id="pay-version-check-result"></span><button type="button" value="' . $module->version . '" id="pay-version-check" class="btn btn-info">' . $l['versionButton'] . '</button></span>',  // phpcs:ignore
              ),
              array(
                'type' => '',
                'label' => $l['Status'],
                'name' => 'PAYNL_STATUS',
                'desc' => '<span class="pay-status">' . $module->payConnection->getHtmlTitle($module) . '</span>', // phpcs:ignore
              ),
              array(
                'type' => 'text',
                'label' => $l['tokenCode'],
                'name' => 'PAYNL_TOKEN_CODE',
                'desc' => $l['findTokenCode'] . '<a href="https://my.pay.nl/company/tokens" target="_blank">' . $l['here'] . '</a>' . $l['signUp'] . '<a target="_blank" href="https://www.pay.nl/en/register-now">' . $l['here'] . '</a>',
                'required' => true
              ),

              array(
                'type' => 'text',
                'label' => $l['apiToken'],
                'name' => 'PAYNL_API_TOKEN',
                'desc' => $l['findApiToken'] . ' ' . '<a href="https://my.pay.nl/company/tokens" target="_blank">' . $l['here'] . '</a>' . $l['signUp'] . '<a target="_blank" href="https://www.pay.nl/en/register-now">' . $l['here'] . '</a>',
                'required' => true,
                'class' => 'obscuredInput'
              ),
              array(
                'type' => 'text',
                'label' => $l['salesLocation'],
                'name' => 'PAYNL_SERVICE_ID',
                'desc' => $l['findSalesLocation'] . '<a href="https://my.pay.nl/programs/programs" target="_blank">' . $l['here'] . '</a>' . $l['signUp'] . '<a target="_blank" href="https://www.pay.nl/en/register-now">' . $l['here'] . '</a>',
                'required' => true
              ),
              array(
                'type' => 'select',
                'label' => $l['multicore'],
                'name' => 'PAYNL_FAILOVER_GATEWAY',
                'desc' => $l['multicoreSettings'] . '<div class="tooltipPAY tooltipPAYsettings tooltipPAYdropdown">?<span class="tooltipPAYtext">' . $l['multicoreTooltip'] . '</span></div>',
                'options' => array(
                  'query' => $this->getCores($module),
                  'id' => 'domain',
                  'name' => 'label'
                )
              ),
              array(
                'type' => 'text',
                'label' => $l['customMulticore'],
                'name' => 'PAYNL_CUSTOM_FAILOVER_GATEWAY',
                'desc' => $l['customMulticoreWarning'],
                'required' => false
              ),
              array(
                'type' => 'text',
                'label' => $l['prefix'],
                'name' => 'PAYNL_DESCRIPTION_PREFIX',
                'desc' => $l['prefixSettings'],
                'required' => false
              ),
              array(
                'type' => 'switch',
                'label' => $l['validationDelay'],
                'name' => 'PAYNL_VALIDATION_DELAY',
                'desc' => $l['validationDelaySettings'],
                'values' => array(
                  array(
                    'id' => 'validation_delay_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'validation_delay_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'switch',
                'label' => $l['logging'],
                'name' => 'PAYNL_PAYLOGGER',
                'desc' => $l['loggingSettings'],
                'values' => array(
                  array(
                    'id' => 'paylogger_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'paylogger_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'switch',
                'label' => $l['testMode'],
                'name' => 'PAYNL_TEST_MODE',
                'desc' => $l['testModeSettings'],
                'values' => array(
                  array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'switch',
                'label' => $l['showImage'],
                'name' => 'PAYNL_SHOW_IMAGE',
                'desc' => $l['showImageSetting'],
                'values' => array(
                  array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'switch',
                'label' => $l['payStyle'],
                'name' => 'PAYNL_STANDARD_STYLE',
                'desc' => $l['payStyleSettings'],
                'values' => array(
                  array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'switch',
                'label' => $l['autoCapture'],
                'name' => 'PAYNL_AUTO_CAPTURE',
                'desc' => $l['autoCaptureSettings'],
                'values' => array(
                  array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'switch',
                'label' => $l['autoVoid'],
                'name' => 'PAYNL_AUTO_VOID',
                'desc' => $l['autoVoidSettings'],
                'values' => array(
                  array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'switch',
                'label' => $l['followPayment'],
                'name' => 'PAYNL_AUTO_FOLLOW_PAYMENT_METHOD',
                'desc' => $l['followPaymentSettings'], // phpcs:ignore
                'values' => array(
                  array(
                    'id' => 'active_on',
                    'value' => 1,
                    'label' => $l['enabled']
                  ),
                  array(
                    'id' => 'active_off',
                    'value' => 0,
                    'label' => $l['disabled']
                  )
                ),
              ),
              array(
                'type' => 'select',
                'label' => $l['language'],
                'name' => 'PAYNL_LANGUAGE',
                'desc' => $l['languageSettings'],
                'options' => array(
                  'query' => $this->getLanguages($module),
                  'id' => 'language_id',
                  'name' => 'label'
                )
              ),
              array(
                'type' => 'text',
                'label' => $l['testIp'],
                'name' => 'PAYNL_TEST_IPADDRESS',
                'desc' => $l['testIpSettings'] . '<br/>' . $l['currentIp'] . Tools::getRemoteAddr(), // phpcs:ignore
                'required' => false
              ),
              array(
                'type' => 'hidden',
                'name' => 'PAYNL_PAYMENTMETHODS',
              )
            ),
            'buttons' => array(
              array(
                'href' => '#feature_request',
                'title' => $l['suggestions'],
                'icon' => 'process-icon-back'
              )
            ),
            'submit' => array(
              'title' => $l['save'],
            )
          ),
        );
    }

    /**
     * @param $module
     * @return array
     */
    public function getConfigFields($module)
    {
        $paymentMethods = json_encode($module->avMethods);
        $showImage = Configuration::get('PAYNL_SHOW_IMAGE');
        $standardStyle = Configuration::get('PAYNL_STANDARD_STYLE');

        $followPaymentMethod = Configuration::get('PAYNL_AUTO_FOLLOW_PAYMENT_METHOD');
        if ($followPaymentMethod === false) {
            $followPaymentMethod = 1;
            Configuration::updateValue('PAYNL_AUTO_FOLLOW_PAYMENT_METHOD', $followPaymentMethod);
        }

        return array(
          'PAYNL_CORE' => Configuration::get('PAYNL_CORE'),
          'PAYNL_API_TOKEN' => Configuration::get('PAYNL_API_TOKEN'),
          'PAYNL_SERVICE_ID' => Configuration::get('PAYNL_SERVICE_ID'),
          'PAYNL_TOKEN_CODE' => Configuration::get('PAYNL_TOKEN_CODE'),
          'PAYNL_TEST_MODE' => Configuration::get('PAYNL_TEST_MODE'),
          'PAYNL_FAILOVER_GATEWAY' => Configuration::get('PAYNL_FAILOVER_GATEWAY'),
          'PAYNL_CUSTOM_FAILOVER_GATEWAY' => Configuration::get('PAYNL_CUSTOM_FAILOVER_GATEWAY'),
          'PAYNL_VALIDATION_DELAY' => Configuration::get('PAYNL_VALIDATION_DELAY'),
          'PAYNL_PAYLOGGER' => Configuration::get('PAYNL_PAYLOGGER'),
          'PAYNL_DESCRIPTION_PREFIX' => Configuration::get('PAYNL_DESCRIPTION_PREFIX'),
          'PAYNL_LANGUAGE' => Configuration::get('PAYNL_LANGUAGE'),
          'PAYNL_SHOW_IMAGE' => $showImage,
          'PAYNL_STANDARD_STYLE' => $standardStyle,
          'PAYNL_AUTO_CAPTURE' => Tools::getValue('PAYNL_AUTO_CAPTURE', Configuration::get('PAYNL_AUTO_CAPTURE')),
          'PAYNL_PAYMENTMETHODS' => $paymentMethods,
          'PAYNL_TEST_IPADDRESS' => Tools::getValue('PAYNL_TEST_IPADDRESS', Configuration::get('PAYNL_TEST_IPADDRESS')),
          'PAYNL_AUTO_VOID' => Tools::getValue('PAYNL_AUTO_VOID', Configuration::get('PAYNL_AUTO_VOID')),
          'PAYNL_AUTO_FOLLOW_PAYMENT_METHOD' => $followPaymentMethod,
        );
    }

    /**
     * @param $module
     * @return array[]
     */
    public function getLanguages($module)
    {
        $l = $module->payTranslations();

        return array(
          array(
            'language_id' => 'nl',
            'label' => $l['dutch']
          ),
          array(
            'language_id' => 'en',
            'label' => $l['english']
          ),
          array(
            'language_id' => 'es',
            'label' => $l['spanish']
          ),
          array(
            'language_id' => 'it',
            'label' => $l['italian']
          ),
          array(
            'language_id' => 'fr',
            'label' => $l['french']
          ),
          array(
            'language_id' => 'de',
            'label' => $l['german']
          ),
          array(
            'language_id' => 'cart',
            'label' => $l['webshopLanguage']
          ),
          array(
            'language_id' => 'auto',
            'label' => $l['browserLanguage']
          ),
        );
    }

  /**
   * @param $module
   * @param $payment_option_id
   * @param $description
   * @param bool $logo
   * @return mixed
   */
    public function getPayForm($module, $payment_option_id, $description = null, bool $logo = true)
    {
        $paymentOptions = [];
        $paymentOptionText = null;
        $paymentDropdownText = null;
        $type = 'dropdown';
        $l = $module->payTranslations();

        if (in_array($payment_option_id, [PaymentMethod::METHOD_INSTORE, PaymentMethod::METHOD_PIN])) {
            try {
                $terminalsFromCache = json_decode(Configuration::get('PAYNL_TERMINALS'), true);
                $allTerminals = $terminalsFromCache['terminals'] ?? [];

                foreach ($allTerminals as $terminal) {
                    $paymentOptions[] = ['id' => $terminal['code'], 'name' => $terminal['name']];
                }

                $paymentOptionText = $l['selectPin'] ?? 'Select terminal';
                $paymentDropdownText = $l['pin'] ?? 'Select terminal';
                $paymentOptionName = 'terminalCode';
            } catch (PayException $e) {
                echo '<pre>';
                echo 'Technical message: ' . $e->getMessage() . PHP_EOL;
                echo 'Pay-code: ' . $e->getPayCode() . PHP_EOL;
                echo 'Customer message: ' . $e->getFriendlyMessage() . PHP_EOL;
                echo 'HTTP code: ' . $e->getcode();
                exit();
            }
        }

        $context = $module->getContext();

        $context->smarty->assign([
            'action' => $context->link->getModuleLink($module->name, 'startPayment', array(), true),
            'payment_option_name' => $paymentOptionName ?? '',
            'payment_options' => $paymentOptions,
            'payment_option_id' => $payment_option_id,
            'payment_option_text' => $paymentOptionText,
            'payment_dropdown_text' => $paymentDropdownText,
            'description' => $description,
            'logoClass' => $logo ? '' : 'noLogo',
            'type' => $type,
        ]);

        return $context->smarty->fetch('module:paynlpaymentmethods/views/templates/front/Pay_payment_form.tpl');
    }

}