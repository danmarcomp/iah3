<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */

global $mod_strings, $app_strings, $theme;
global $timedate;

require_once 'XTemplate/xtpl.php';
require_once 'themes/' . $theme . '/layout_utils.php';
require_once 'include/CCGateway/Base.php';
require_once 'modules/Invoice/Invoice.php';
require_once 'modules/Accounts/Account.php';
require_once 'modules/Contacts/Contact.php';
require_once('modules/Currencies/Currency.php');
require_once('modules/Payments/Payment.php');

$payment = new Payment;
if (empty($_REQUEST['payment_id']) || !$payment->retrieve($_REQUEST['payment_id']) || $payment->deleted) {
	sugar_die('Invalid payment ID');
}

$xtpl = new XTemplate('modules/Payments/PopupCardRefund.html');
$xtpl->assign('MOD', $mod_strings);
$xtpl->assign('APP', $app_strings);

$xtpl->assign('PAYMENT_ID', $payment->id);

$currency = new Currency();
$currency->retrieve($payment->currency_id);

$merchant_currency = new Currency();
$merchant_currency->retrieve(AppConfig::setting('company.merchant_currency'));

$symbol = $currency->symbol;
$xtpl->assign('INVOICE_TOTAL', $symbol.'&nbsp;'. format_number($payment->amount, 2, 2));

$merchant_total = $payment->amount;

if ($merchant_currency->id != $currency->id) {
	$symbol = $merchant_currency->symbol;
	$merchant_total = $merchant_currency->convertFromDollar($currency->convertToDollar($payment->amount));
	$xtpl->assign('MERCHANT_TOTAL', '(' . $symbol.'&nbsp;'. format_number($merchant_total, 2, 2) . ')');
}

$xtpl->assign('SYMBOL', $symbol);

$types = CCGatewayBase::getCardTypes(AppConfig::setting('company.payment_gateway'));
$xtpl->assign('CARD_TYPE_OPTIONS', get_select_options_with_id($types, @$_REQUEST['cardType']));
$xtpl->assign('CARD_NUMBER', @$_REQUEST['cardNumber']);
$xtpl->assign('CARD_EXPIRATION', @$_REQUEST['cardExpiration']);
$xtpl->assign('CARD_CVV', @$_REQUEST['cardCVV']);

if (!isset($_REQUEST['paymentAmount'])) {
	$xtpl->assign('PAYMENT_AMOUNT', format_number($merchant_total, 2, 2));
} else {
	$xtpl->assign('PAYMENT_AMOUNT', $_REQUEST['paymentAmount']);
}

$error = null;

if (!empty($_REQUEST['do_process'])) {
	$options = array(
		'x_trans_id' => $payment->customer_reference,
	);
	$process = CCGatewayBase::create(AppConfig::setting('company.payment_gateway'), $options, PAYMENT_PROCESS_ACTION_CREDIT);
	$process->setLogin(AppConfig::setting('company.merchant_login'), AppConfig::setting('company.merchant_password'));
	$process->setAmount($merchant_total);
	$process->setCardType($_REQUEST['cardType']);
	$process->setCardNumber($_REQUEST['cardNumber']);
	$process->setCardExpDate($_REQUEST['cardExpiration']);
	$process->setCVV($_REQUEST['cardCVV']);
	$result = $process->process();
	if (Pear::isError($result)) {
		$error = $result->getMessage();
	} else {

		// 0 : no transaction recorded
		// 1 : success
		// 2 : transaction under merchant review
		// 3 : transaction under authorize.net review
		$success = 0;

		$error = '';
		$warning = '';

		switch ($result->code) {

			case 1:
				$success = 1;
				break;

			case 4:
				switch ($result->messageCode) {
					case 193: // the transaction is currently under review
						$success = 3;
						$warning = $mod_strings['LBL_CC_AUTHNET_REVIEW'];
						break;
					case 252: // the transaction is held for merchant review
					case 253: // the transaction is held for merchant review
						$success = 2;
						$warning = $mod_strings['LBL_CC_MERCHANT_REVIEW'];
						break;
				}
			default:
				$error = $result->getMessage();
				break;

		}

		$avsMessage =  $result->getAVSMessage();
		if ($avsMessage) {
			$avsMessage = $mod_strings['LBL_CC_AVS_RESULT'] . $avsMessage;
		}

		if ($success) {
			$payment->refunded = true;
			$payment->save();
			add_flash_message($mod_strings['LBL_CC_SUCCESS'], 'info');
			if ($warning) {
				add_flash_message($warning, 'warn');
			}
			if ($avsMessage) {
				add_flash_message($avsMessage, 'warn');
			}
			echo '<script type="text/javascript">window.location.href="index.php?module=Payments&action=DetailView&record=' . $payment->id . '"; var popup = SUGAR.popups.getCurrent(); if (popup) popup.close()  </script>';
			exit;
		}
	}
}


if (!empty($error)) {
	$xtpl->assign('ERROR', $error);
	$xtpl->parse('main.errors.error');

 	if ($avsMessage) {
		$xtpl->assign('ERROR', $avsMessage);
		$xtpl->parse('main.errors.error');
 	}
 }
$xtpl->parse('main.errors');

insert_popup_header($theme);
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_CC_REFUND_TITLE'], false);
$xtpl->parse('main');
$xtpl->out('main');
insert_popup_footer();

