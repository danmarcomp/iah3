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

$qDone = "SELECT * FROM versions WHERE name = 'Upgrade Quotes/Invoices'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	$done = false;
}

require_once('modules/Quotes/Quote.php');
require_once('modules/Quotes/QuoteLineGroup.php');
require_once('modules/Invoice/Invoice.php');
require_once('modules/Invoice/InvoiceLineGroup.php');

$recalc_totals = false;
$test_run = false;


function convert_line_items(&$seed, &$grpseed) {
	global $recalc_totals, $test_run;

	$list_query = "SELECT tbl.id, tbl.amount, tbl.amount_usdollar, tbl.currency_id, tbl.exchange_rate, tbl.line_items FROM `$seed->table_name` tbl";
	$list_query .= " LEFT JOIN `$grpseed->table_name` grps ON grps.parent_id=tbl.id WHERE grps.id IS NULL";
	$result = $seed->db->query($list_query, true, "Error retrieving {$seed->object_name} list");
	$data = array();
	while($row = $seed->db->fetchByAssoc($result,-1,false))
		$data[$row['id']] = $row;
	
	foreach(array_keys($data) as $idx) {
		$seed->id = $idx;
		$seed->line_items = unserialize(base64_decode($data[$idx]['line_items']));
		$seed->amount = $data[$idx]['amount'];
		$seed->amount_usdollar = $data[$idx]['amount_usdollar'];
		$seed->currency_id = $data[$idx]['currency_id'];
		$seed->exchange_rate = $data[$idx]['exchange_rate'];
	
		$lg =& $grpseed->newForParent($seed);
		$grps =& $lg->groupsFromLineItems($seed->line_items, $recalc_totals);
		$new_li =& $lg->lineItemsFromGroups($grps);
		$total = $grps['GRANDTOTAL']->total;
		if($test_run) {
			$changed = $total == $seed->amount ? '' : ' ***';
			pr2($seed->id.'  '.format_number($total).'  '.format_number($seed->amount).$changed);
		}
		else {
			foreach(array_keys($grps) as $gidx)
				$grps[$gidx]->save();
			if(empty($seed->exchange_rate)) {
				if($seed->amount_usdollar)
					$seed->exchange_rate = sprintf('%0.5f', $seed->amount / $seed->amount_usdollar);
				else {
					$currency =& $grpseed->get_currency($seed->currency_id);
					$seed->exchange_rate = $currency->conversion_rate;
				}
			}
			$total_usd = $total / $seed->exchange_rate;
			$query = sprintf(
				"UPDATE `$seed->table_name` SET line_items='%s', amount='%s', amount_usdollar='%s', exchange_rate='%s' WHERE id='%s'",
				$seed->db->quote(base64_encode(serialize($new_li))),
				$total, $total_usd,
				$seed->exchange_rate,
				$seed->id
			);
			$seed->db->query($query, true);
			pr2("Updated $seed->object_name $seed->id");
		}
	}
}


if(! $done) {
	$confirm = ! empty($_REQUEST['confirm']);
	if($confirm) {
		$quote = new Quote();
		$qlg = new QuoteLineGroup();
		convert_line_items($quote, $qlg);
		
		$invoice = new Invoice();
		$ilg = new InvoiceLineGroup();
		convert_line_items($invoice, $ilg);
		
		require_once('modules/Versions/Version.php');
		$ver = new Version();
		$ver->name = 'Upgrade Quotes/Invoices';
		$ver->file_version = $ver->db_version = '4.2.2';
		$ver->save();
		unset($_SESSION['upgrade_quotes_invoices']);
		
		pr2('Upgrade complete.');
	}
	else {
		echo translate('LBL_UPGRADE_QUOTES_INVOICES_CONFIRM', 'Administration');
		$btn_label = htmlentities(translate('LBL_CONFIRM_BUTTON', 'Administration'));
		echo <<<EOF
			<form action="index.php" method="POST">
			<input type="hidden" name="module" value="Administration">
			<input type="hidden" name="action" value="UpgradeQuotesInvoices">
			<input type="hidden" name="confirm" value="true">
			<input type="submit" value="$btn_label">
			</form>
EOF;
	}
}
else
	echo translate('LBL_UPGRADE_PERFORMED', 'Administration');

?>