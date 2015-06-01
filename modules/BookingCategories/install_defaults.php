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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('modules/BookingCategories/BookingCategory.php');
require_once('modules/TaxCodes/TaxCode.php');


function getDefaultBookingCategories()
{
	return array(
		'billable-work' => array(
			'LBL_BILLED_CONTRACT_WORK',
			'LBL_BILLED_CUSTOMER_SERVICE',
			'LBL_OTHER_BILLED_SERVICE',
		),
		'nonbill-work' => array(
			'LBL_OFFICE_WORK',
			'LBL_GENERAL_MAINTENANCE',
			'LBL_LEAD_DEVELOPMENT',
			'LBL_BID_DEVELOPMENT',
			'LBL_PRODUCT_DEVELOPMENT',
			'LBL_PROMOTIONS',
			'LBL_OTHER_NON_BILLABLE',
		),
		'expenses' => array(
			'LBL_PERSONAL_AUTO' => array('expenses_unit' => 'km'),
			'LBL_AUTO_RENTAL',
			'LBL_OTHER_TRANSPORTATION',
			'LBL_MEALS_EXPENSES',
			'LBL_LODGING_EXPENSES',
			'LBL_TELEPHONE_EXPENSES',
			'LBL_UTILITIES_EXPENSES' => array('expenses_unit' => 'month'),
			'LBL_OTHER_EXPENSES',
		),
		'services-monthly' => array(
		),
	);
}

function populateBookingCategories() {
	$sample = getDefaultBookingCategories();
	foreach($sample as $cls => $ns) {
		foreach($ns as $n => $vs) {
			if(is_int($n)) $n = $vs;
			if(! is_array($vs)) $vs = array();
			$seed = new BookingCategory();
			$seed->name = translate($n, 'BookingCategories');
			$seed->booking_class = $cls;
			if($cls == 'billable-work' || $cls == 'billable-expenses')
				$seed->tax_code_id = STANDARD_TAXCODE_ID;
			else
				$seed->tax_code_id = '-99';
			if($cls != 'expenses')
				$seed->duration = 'hour';
			foreach($vs as $k => $v)
				$seed->$k = $v;
			$seed->save();
			$seed->cleanup();
		}
	}
}

if(! defined('IAH_IN_INSTALLER')) {
	if(is_admin($current_user)) {
		populateBookingCategories();
		print 'Default booking categories populated';
	}
}


?>
