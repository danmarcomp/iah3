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
//if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
// Include the accounts detailview
include_once('modules/Invoice/Invoice.php');
require_once('modules/Invoice/InvoiceLineGroup.php');
require_once('modules/Invoice/InvoiceLine.php');
require_once('modules/Invoice/InvoiceAdjustment.php');
require_once('modules/Payments/Payment.php');


function json_save_allocate_payment() {
	// Get access to the global objects
	global $db;
	// Ensure that we have valid payment data and invoice ids
	if(empty($_REQUEST['payment'])) {
		// Indicate the error
		json_server_error("No payment info specified");
		// Nothing else to do
		return false;
	}
	// Ensure that we have valid payment data and invoice ids
	if(empty($_REQUEST['invoice_ids'])) {
		// Indicate the error
		json_server_error("No invoice info specified");
		// Nothing else to do
		return false;
	}
	// Ensure that the admin user is valid
	$objAdminUser = new User();
	// Do we have a valid user?
	if (!$objAdminUser->retrieve_by_string_fields(array('user_name' => $_REQUEST['admin_user'], 'user_hash' => $_REQUEST['admin_pass'], 'is_admin' => 1, 'status' => 'Active', 'deleted' => 0))) {
		// Indicate the error
		return json_bad_request(array('error'=> 'invalid_admin_user'));
	}
	// Finally, create the payment
	$objPayment = new Payment();
	// Set the payment info
	$objPayment->assigned_user_id = $objAdminUser->id;
	$objPayment->name = $_REQUEST['payment']['name'];
	$objPayment->description = $_REQUEST['payment']['description'];
	$objPayment->account_id = $_REQUEST['payment']['account_id'];
	$objPayment->payment_date = date('Y/m/d');
	$objPayment->payment_type = $_REQUEST['payment']['payment_type'];
	$objPayment->direction = $_REQUEST['payment']['direction'];
	$objPayment->amount = $objPayment->amount_usd = $_REQUEST['payment']['amount'];
	// Save the payment
	$strPaymentId = $objPayment->save();
	// Get the invoice amounts
	$strQuery = "
		SELECT
			id,
			amount
		FROM
			invoice
		WHERE
			id IN ('" . implode("','", $_REQUEST['invoice_ids']) . "')
	";
	// Execute the query
	$objResult = $db->query($strQuery);
	// Loop through and process the results
	while (($arRow = $db->fetchByAssoc($objResult)) != null) {
		// Create the guid
		$strId = create_guid();
		// Create the payment allocation
		$strQuery = "INSERT INTO invoices_payments SET "
			. "`id`='{$strId}'"
			. ", `invoice_id`='" . PearDatabase::quote($arRow['id']) . "'"
			. ", `payment_id`='" . PearDatabase::quote($strPaymentId) . "'"
			. ", `amount`='" . PearDatabase::quote($arRow['amount']) . "'"
			. ", `exchange_rate`=1"
			. ", `amount_usdollar`='" . PearDatabase::quote($arRow['amount']) . "'"
			. ", `date_modified`='" . gmdate('Y-m-d H:i:s') . "'";
		// Execute the query
		$db->query($strQuery, true);
	}
	// Create the payment object
	$objPayment = new Payment();
	// Update the payment
	$objPayment->retrieve($strPaymentId);
	// Payment id
	$objPayment->save();
	// Create the return array
	$arReturn = array (
		'result' => true,
		'payment_id' => $strPaymentId,
	);
	// Return the generated array
	json_return_value($arReturn);
}

$json_supported_actions['save_allocate_payment'] = array('login_required' => false);

?>