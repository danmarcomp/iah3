<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/

function perform_save(&$focus){
	require_once('modules/Currencies/Currency.php');
	//US DOLLAR
	if(isset($focus->amount) && !number_empty($focus->amount)){
		$currency = new Currency();
		$currency->retrieve($focus->currency_id);

		/* longreach - removed - DO NOT manually unformat values - use unformat_all_fields
		$focus->amount_usdollar = $currency->convertToDollar(unformat_number($focus->amount));
		*/
		// longreach - start added
		$rate_changed = adjust_exchange_rate($focus, $currency);
		$focus->amount_usdollar = $currency->convertToDollar($focus->amount);
		// longreach - end added
		
	}
	
	// longreach - start added
	else {
		$focus->amount_usdollar = 0;
		$focus->exchange_rate = null;
	}
	// --
	require_once('include/TimeDate.php');
	$timedate = new TimeDate();
	if(preg_match('/^Closed .*/', $focus->sales_stage) &&
		(empty($focus->fetched_row['sales_stage']) || $focus->fetched_row['sales_stage'] != $focus->sales_stage)) {
			// if opportunity has closed and the close date is in the future then make it today.
			$closed = $timedate->to_db_date($focus->date_closed, false);
			$now = date('Y-m-d');
			if($closed > $now || empty($focus->date_closed)) {
				$focus->date_closed = $timedate->to_display_date($now, false);
			}
	}
	// longreach - end added

}
?>
