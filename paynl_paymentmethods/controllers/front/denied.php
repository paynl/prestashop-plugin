<?php

class paynl_paymentmethodsDeniedModuleFrontController extends ModuleFrontController
{

    public function initContent()
    {
        $errorMessage = $this->context->cookie->redirect_errors;
        $link = 'index.php?controller=order&step=3';

        $message = $this->module->l('The transaction has been denied by the paymentmethod') . '.</br>';
        $message .= $this->module->l('Please choose another paymentmethod to complete the order') . '.</br>';
        $message .= $this->module->l('Click on proceed to continue');

        $this->context->smarty->assign('title', $this->module->l('Payment denied'));
        $this->context->smarty->assign('proceedurl', $link);
        $this->context->smarty->assign('proceed_message', $this->module->l('Proceed'));
        $this->context->smarty->assign('messsage', $message);
        $this->context->smarty->assign('error_message', $errorMessage);

        $this->setTemplate('denied.tpl');
    }

}
