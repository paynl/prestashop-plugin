<?php

function upgrade_module_3_5_0($module)
{
	/** @var paynl_paymentmethods $module */

	$module->registerHook('displayPaymentEU', false);

	return true;
}
