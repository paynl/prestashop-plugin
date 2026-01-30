<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PaynlPaymentMethods\PrestaShop\PayHelper;

/**
 * @since 1.5.0
 */
class PaynlPaymentMethodsStartPaymentModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        $paymentOptionId = Tools::getValue('payment_option_id');
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'paynlpaymentmethods') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            Tools::redirect('/index.php?controller=order&step=1');
            return null;
        }

        try {
            $redirectUrl = $this->module->startPayment($cart, $paymentOptionId, ['terminalCode' => Tools::getValue('terminalCode'), 'pinMoment' => Tools::getValue('pinMoment')]);
            Tools::redirect($redirectUrl);
        } catch (Throwable $e) {
            (new PayHelper())->payLog('postProcess', 'Error startPayment: ' . $e->getMessage(), $cart->id);
            $this->warning[] = $this->module->l($e->getMessage(), 'startpayment');
            $this->redirectWithNotifications('index.php?controller=order&step=1');
        }
    }
}