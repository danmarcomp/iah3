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


$qDone = "SELECT * FROM versions WHERE name = 'Bills Associations'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	$done = false;
}


if(! $done) {

	global $unzip_dir;
	global $adm_users, $db;

	require_once 'modules/PurchaseOrders/PurchaseOrder.php';
	require_once 'modules/Bills/Bill.php';

	$query = "SELECT invoices_payments.invoice_id FROM bills LEFT JOIN invoices_payments ON invoices_payments.invoice_id = bills.id";
	$res = $db->query($query, false);
	if (!$res || !($row = $db->fetchByAssoc($res)) || empty($row['invoice_id'])) {
		$query = "SELECT purchase_orders.id AS po_id, invoices_payments.id AS ip_id, payments.id AS payment_id from purchase_orders LEFT JOIN invoices_payments ON invoices_payments.invoice_id=purchase_orders.id LEFT JOIN payments ON payments.id=invoices_payments.payment_id WHERE purchase_orders.deleted = 0 AND invoices_payments.deleted=0 AND payments.deleted = 0 ORDER BY purchase_orders.id";

		$lid = '';
		$res = $db->query($query, true);
		while ($row = $db->fetchByAssoc($res)) {
			if ($lid != $row['po_id']) {
				$lid = $row['po_id'];
				$po = new PurchaseOrder;
				$po->retrieve($lid);
				$bill = new Bill;
				$bill->id = create_guid();
				$bill->new_with_id = true;
				$bill->initFromPurchaseOrder($po);
				$groups =& $bill->get_line_groups(true);
				$enc = new $bill->group_object_name();
				$enc = $enc->newForParent($bill);
				$items = $enc->convert_to_array($groups, true);
				$bill->line_groups = array();
				$enc->update_from_array($bill->line_groups, $items);
				foreach ($bill->line_groups as $i => $lg) {
					$bill->line_groups[$i]->bills_id = $bill->id;
					$bill->line_groups[$i]->parent_id = $bill->id;
					if (is_array($lg->lines)) {
						foreach ($lg->lines as $j => $l) {
							$bill->line_groups[$i]->lines[$j]['bills_id'] = $bill->id;
						}
					}
				}
				$bill->save();
			}
			$query = sprintf("UPDATE invoices_payments SET invoice_id = '%s' WHERE id='%s'", $bill->id, $row['ip_id']);
			$db->query($query, true);
		}
	}

	require_once('modules/Versions/Version.php');
	unset($_SESSION['upgrade_bills']);
	Version::mark_upgraded('Bills Associations', '5.3.1', '5.3.1');
	echo translate('LBL_UPGRADE_COMPLETE', 'Administration');

} else {
	echo translate('LBL_UPGRADE_PERFORMED', 'Administration');
}


