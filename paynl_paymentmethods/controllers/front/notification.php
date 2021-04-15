<?php

class paynl_paymentmethodsNotificationModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        if ($_GET['redirect'] == 1) {
            Tools::redirect('index.php?controller=order');
        }

        $errorMessage = $this->context->cookie->redirect_errors;
        $link = Context::getContext()->link->getModuleLink('paynl_paymentmethods', 'notification') . '&redirect=1';

        $message = $this->module->l('The order could not be made with this paymentmethod due to either the order amount being to high or to low or the payment being denied') . '.</br>';
        $message .= $this->module->l('Please try again, or choose another paymentmethod') . '.</br>';
        $message .= $this->module->l('Click on the link to continue');

        $this->context->smarty->assign('title', $this->module->l('Unfortenately something went wrong'));
        $this->context->smarty->assign('proceedurl', $link);
        $this->context->smarty->assign('proceed_message', $this->module->l('Proceed'));
        $this->context->smarty->assign('messsage', $message);
        $this->context->smarty->assign('error_message', $errorMessage);

        $this->setTemplate('notification.tpl');
    }

}
