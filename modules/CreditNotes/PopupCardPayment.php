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

$invoice = new Invoice;
if (empty($_REQUEST['invoice_id']) || !$invoice->retrieve($_REQUEST['invoice_id']) || $invoice->deleted) {
	sugar_die('Invalid invoice ID');
}

$xtpl = new XTemplate('modules/Invoice/PopupCardPayment.html');
$xtpl->assign('MOD', $mod_strings);
$xtpl->assign('APP', $app_strings);

$xtpl->assign('INVOICE_ID', $invoice->id);

$currency = new Currency();
$currency->retrieve($invoice->currency_id);

$merchant_currency = new Currency();
$merchant_currency->retrieve(AppConfig::setting('company.merchant_currency'));

$symbol = $currency->symbol;
$xtpl->assign('INVOICE_TOTAL', $symbol.'&nbsp;'. format_number($invoice->amount, 2, 2));
$xtpl->assign('INVOICE_DUE', $symbol.'&nbsp;'. format_number($invoice->amount_due, 2, 2));
$xtpl->assign('INVOICE_SUBJECT', $invoice->name);

$merchant_total = $invoice->amount;
$merchant_due = $invoice->amount_due;

if ($merchant_currency->id != $currency->id) {
	$symbol = $merchant_currency->symbol;
	$merchant_total = $merchant_currency->convertFromDollar($currency->convertToDollar($invoice->amount));
	$merchant_due = $merchant_currency->convertFromDollar($currency->convertToDollar($invoice->amount_due));
	$xtpl->assign('MERCHANT_TOTAL', '(' . $symbol.'&nbsp;'. format_number($merchant_total, 2, 2) . ')');
	$xtpl->assign('MERCHANT_DUE', '(' . $symbol.'&nbsp;'. format_number($merchant_due, 2, 2) . ')');
}

$xtpl->assign('SYMBOL', $symbol);

$types = CCGatewayBase::getCardTypes(AppConfig::setting('company.payment_gateway'));
$xtpl->assign('CARD_TYPE_OPTIONS', get_select_options_with_id($types, @$_REQUEST['cardType']));
$xtpl->assign('CARD_NUMBER', @$_REQUEST['cardNumber']);
$xtpl->assign('CARD_EXPIRATION', @$_REQUEST['cardExpiration']);
$xtpl->assign('CARD_CVV', @$_REQUEST['cardCVV']);

if (isset($_REQUEST['name'])) {
	$xtpl->assign('NAME', $_REQUEST['name']);
} else {
	if (trim($invoice->billing_contact_name) !== '') {
		$xtpl->assign('NAME', $invoice->billing_contact_name);
	} else {
		$account = new Account;
		$account->retrieve($invoice->billing_account_id);
		if (!empty($account->primary_contact_id)) {
			$contact = new Contact;
			$contact->retrieve($account->primary_contact_id);
			$xtpl->assign('NAME', $contact->first_name . ' ' . $contact->last_name);
		} elseif (!empty($invoice->billing_account_name)) {
			$xtpl->assign('NAME', $invoice->billing_account_name);
		}
	}
}

if (isset($_REQUEST['address'])) {
	$xtpl->assign('ADDRESS', $_REQUEST['address']);
} else {
	$xtpl->assign('ADDRESS', $invoice->billing_address_street);
}

if (isset($_REQUEST['city'])) {
	$xtpl->assign('CITY', $_REQUEST['city']);
} else {
	$xtpl->assign('CITY', $invoice->billing_address_city);
}

if (isset($_REQUEST['state'])) {
	$xtpl->assign('STATE', $_REQUEST['state']);
} else {
	$xtpl->assign('STATE', $invoice->billing_address_state);
}

if (isset($_REQUEST['country'])) {
	$xtpl->assign('COUNTRY', $_REQUEST['country']);
} else {
	$xtpl->assign('COUNTRY', $invoice->billing_address_country);
}

if (isset($_REQUEST['postalCode'])) {
	$xtpl->assign('POSTAL_CODE', $_REQUEST['postalCode']);
} else {
	$xtpl->assign('POSTAL_CODE', $invoice->billing_address_postalcode);
}

if (isset($_REQUEST['email'])) {
	$xtpl->assign('EMAIL', $_REQUEST['email']);
} else {
	if (!empty($invoice->billing_contact_name)) {
		$contact = new Contact;
		$contact->retrieve($invoice->billing_contact_id);
		$xtpl->assign('EMAIL', $contact->email1);
	} else {
		$account = new Account;
		$account->retrieve($invoice->billing_account_id);
		$xtpl->assign('EMAIL', $account->email1);
	}
}

if (isset($_REQUEST['phone'])) {
	$xtpl->assign('PHONE', $_REQUEST['phone']);
} else {
	if (!empty($invoice->billing_contact_name)) {
		$contact = new Contact;
		$contact->retrieve($invoice->billing_contact_id);
		$xtpl->assign('PHONE', $contact->phone_home);
	} else {
		$account = new Account;
		$account->retrieve($invoice->billing_account_id);
		$xtpl->assign('PHONE', $account->phone_office);
	}
}

if (!isset($_REQUEST['paymentAmount'])) {
	$xtpl->assign('PAYMENT_AMOUNT', format_number($merchant_due, 2, 2));
} else {
	$xtpl->assign('PAYMENT_AMOUNT', $_REQUEST['paymentAmount']);
}

$error = null;

if (!empty($_REQUEST['do_process'])) {
	$process = CCGatewayBase::create(AppConfig::setting('company.payment_gateway'));
	$process->setLogin(AppConfig::setting('company.merchant_login'), AppConfig::setting('company.merchant_password'));
	$process->setAmount(unformat_number($_REQUEST['paymentAmount']));
	$process->setCardType($_REQUEST['cardType']);
	$process->setCardNumber($_REQUEST['cardNumber']);
	$process->setName($_REQUEST['name']);
	$process->setAddress($_REQUEST['address']);
	$process->setCity($_REQUEST['city']);
	$process->setState($_REQUEST['state']);
	$process->setCountry($_REQUEST['country']);
	$process->setPostalCode($_REQUEST['postalCode']);
	$process->setEmail($_REQUEST['email']);
	$process->setPhone($_REQUEST['phone']);
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
			require_once 'modules/Payments/Payment.php';
			$payment = new Payment;
			$payment->direction = 'incoming';
			$payment->id = create_guid();
			$payment->new_with_id = true;
			$payment->amount = unformat_number($_REQUEST['paymentAmount']);
			$payment->currency_id = AppConfig::setting('company.merchant_currency');
			$payment->account_id = $invoice->billing_account_id;
			$payment_date = gmdate('Y-m-d H:i:s');
			$payment_date = $timedate->handle_offset($payment_date, 'Y-m-d H:i:s', true, $current_user);
			$payment->payment_date = $timedate->to_display($payment_date, 'Y-m-d H:i:s', $timedate->get_date_time_format());
			$payment->customer_reference = $result->transactionId;
			$payment->payment_type = 'Credit Card';

			$item = array(
				'invoices_payments_id' => create_guid(),
				'invoice_id' => $invoice->id,
				'payment_id' => $payment->id,
				'amount' => $currency->convertFromDollar($merchant_currency->convertToDollar(unformat_number($_REQUEST['paymentAmount']))),
				'amount_usdollar' => $merchant_currency->convertToDollar(unformat_number($_REQUEST['paymentAmount'])),
				'exchange_rate' => $currency->conversion_rate,
				'deleted' => 0,
				'date_modified' => gmdate("Y-m-d H:i:s"),
			);
			$payment->line_items = array($item);
			$payment->save();
			add_flash_message($mod_strings['LBL_CC_SUCCESS'], 'info');
			add_flash_message(sprintf($mod_strings['LBL_CC_TXN_ID_IS'], $result->transactionId), 'info');
			add_flash_message('<a href="index.php?module=Payments&action=DetailView&record=' . $payment->id .   '">'. $mod_strings['LBL_CC_VIEW_PAYMENT'] . '</a>', 'info');
			if ($warning) {
				add_flash_message($warning, 'warn');
			}
			if ($avsMessage) {
				add_flash_message($avsMessage, 'warn');
			}
			echo '<script type="text/javascript">window.opener.location.href="index.php?module=Invoice&action=DetailView&record=' . $invoice->id . '"; window.close(); </script>';
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
echo get_module_title($mod_strings['LBL_MODULE_NAME'], $mod_strings['LBL_CC_TITLE'], false);
$xtpl->parse('main');
$xtpl->out('main');
insert_popup_footer();

