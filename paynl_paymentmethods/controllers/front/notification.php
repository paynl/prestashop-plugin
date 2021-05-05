<?php

class paynl_paymentmethodsNotificationModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        if ($_GET['redirect'] == 1) {
            Tools::redirect('index.php?controller=order');
        }

        $errorMessage = $this->context->cookie->redirect_errors;
        $link = 'index.php?controller=order&step=3';

        $message = $this->module->l('The transaction has unfortunately been denied') . '.</br>';
        $message .= $this->module->l('Please try again, or choose another paymentmethod') . '.</br>';
        $message .= $this->module->l('Click on proceed to continue');

        $this->context->smarty->assign('title', $this->module->l('Transaction denied'));
        $this->context->smarty->assign('proceedurl', $link);
        $this->context->smarty->assign('proceed_message', $this->module->l('Proceed'));
        $this->context->smarty->assign('messsage', $message);
        $this->context->smarty->assign('error_message', $errorMessage);

        $this->setTemplate('notification.tpl');
    }

}
