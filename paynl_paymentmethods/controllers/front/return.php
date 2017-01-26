<?php

/*
 * 2007-2014 PrestaShop
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
 *  @copyright  2007-2014 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

/**
 * @since 1.5.0
 */
class paynl_paymentmethodsReturnModuleFrontController extends ModuleFrontController {

    public function initContent() {
        parent::initContent();
        $transactionId = Tools::getValue('orderId');

        try {
            $result = Pay_Helper_Transaction::processTransaction($transactionId, true);

            $order = new Order($result['real_order_id']);
            $customer = new Customer($order->id_customer);

            $this->context->smarty->assign(array(
                'reference_order' => $result['real_order_id'],
                'email' => $customer->email,
                'id_order_formatted'=> $order->reference,
            ));
            $slowvalidation = '';
            if(!($result['real_order_id'])){
                $slowvalidation = "&slowvalidation=1";
            }
            if ($result['state'] == 'PAID') {
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$result['orderId'].'&id_module='.$this->module->id.'&id_order='.$result['real_order_id'].'&key='.$customer->secure_key.$slowvalidation);
             
            }
            if ($result['state'] == 'CHECKAMOUNT') {
                $this->setTemplate('return_checkamount.tpl');
            }
            if ($result['state'] == 'CANCEL') {
                if(!empty($result['real_order_id'])){
                    Tools::redirect('index.php?controller=order&submitReorder=Reorder&id_order='.$result['real_order_id']);
                }else {
                    Tools::redirect('index.php?controller=order');
                }
            }
            if ($result['state'] == 'PENDING') {
                Tools::redirect('index.php?controller=order-confirmation&id_cart='.$result['orderId'].'&id_module='.$this->module->id.'&id_order='.$result['real_order_id'].'&key='.$customer->secure_key);
            }
        } catch (Exception $ex) {

            echo 'Error: ' . $ex->getMessage();
            die();
        }
    }

}
