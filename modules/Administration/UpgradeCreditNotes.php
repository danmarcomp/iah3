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


action_restricted_for('demo');

global $current_user;
if(! is_admin($current_user))
	die('Admin Only Section');

global $db;

require_once('modules/Versions/Version.php');
$qDone = "SELECT * FROM versions WHERE name = 'Credit Notes'";
$rDone = $db->query($qDone);

$performed = false;
$done = '';
while( ($row = $db->fetchByAssoc($rDone)) ) {
	$done = $row['db_version'];
}

if(! $done) {
		$db->query("UPDATE invoice SET credit_note = 1 WHERE amount < 0");
		unset($_SESSION['upgrade_credit_notes']);
		Version::mark_upgraded('Credit Notes', '5.3.2', '5.3.2');
		echo translate('LBL_UPGRADE_CREDIT_NOTES_COMPLETE', 'Administration');
		$done = '5.3.2';
		$performed = true;
}

if($done != '6.6.0') {
	$bug = ('6.5.0' == $done);

	require_once('modules/Invoice/Invoice.php');

	if ($bug) {
		$result = $db->query("SELECT * FROM versions WHERE name='Credit Notes'");
		$row = $db->fetchByAssoc($result);
		$upgrade_time = $row['date_entered']; // Version::mark_upgraded() deletes old record
		$upgrade_date = substr($row['date_entered'], 0, 10) . ' 12:00:00'; // Version::mark_upgraded() deletes old record
	} else {
		$upgrade_date = '2999-12-31';
		$upgrade_time = '2999-12-31 23:59:59';
		$db->query("UPDATE invoice SET credit_note = 1 WHERE amount < 0");
	}

	$query = "SELECT id FROM invoice WHERE credit_note=1 AND date_entered <='$upgrade_date'";
	$result = $db->query($query);
	$upgrade_ids = array();
	while( ($row = $db->fetchByAssoc($result)) )
		$upgrade_ids[] = $row['id'];
	$GLOBALS['disable_date_format'] = true;
	foreach($upgrade_ids as $invoice_id) {
		$query = "SELECT payment_id, SUM(IFNULL(amount, 0)) AS amount, SUM(IFNULL(amount_usdollar, 0)) AS amount_usdollar FROM invoices_payments WHERE invoice_id = '$invoice_id' AND date_modified <= '$upgrade_time' AND deleted=0 GROUP BY payment_id";
		$result = $db->query($query);
		while ($row = $db->fetchByAssoc($result)) {
			$query = "UPDATE payments SET amount = amount - {$row['amount']}*2, amount_usdollar = amount_usdollar - {$row['amount_usdollar']}*2 WHERE id = '{$row['payment_id']}'";
			$db->query($query, true);
		}

		$query = "UPDATE invoices_payments SET amount = -amount, amount_usdollar = -amount_usdollar WHERE invoice_id = '$invoice_id' AND date_modified <= '$upgrade_time'";
		$db->query($query, true);

		$credit_note = new Invoice();
		if(! $credit_note->retrieve($invoice_id))
			continue;
		$grps =& $credit_note->get_line_groups();
		$mgr = $credit_note->get_line_group_manager();
		$grp_arr = $mgr->convert_to_array($grps);
		foreach($grp_arr as &$grp) {
			if(empty($grp['lines']))
				continue;
			foreach($grp['adjusts'] as &$line) {
				$needsFix = ($line['type'] != 'TaxedShipping' && $line['type'] != 'UntaxedShipping') || !$bug;
				if($needsFix)
					$line['amount'] = - $line['amount'];
				unset($line);
			}
			unset($grp);
		}
		$mgr->update_from_array($grps, $grp_arr, true, null, false);
		$credit_note->update_date_modified = false;
		$credit_note->update_modified_by = false;
		$credit_note->update_taxes = false;
		$credit_note->save();
		echo '. ';
	}
	echo '<br>';
	$GLOBALS['disable_date_format'] = false;
	
	require_once('modules/Versions/Version.php');
	unset($_SESSION['upgrade_credit_notes']);
	Version::mark_upgraded('Credit Notes', '6.6.0', '6.6.0');
	echo translate('LBL_UPGRADE_CREDIT_NOTES_COMPLETE_2', 'Administration');
	$done = '6.6.0';
	$performed = true;
}

if (!$performed)
	echo translate('LBL_UPGRADE_PERFORMED', 'Administration');

?>
