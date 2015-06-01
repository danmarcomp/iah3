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
// vim: set foldmethod=marker :
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


action_restricted_for('demo');

global $current_user;
if(! is_admin($current_user))
	die('Admin Only Section');


global $db;


$qDone = "SELECT * FROM versions WHERE name = 'Customers/Vendors'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	$done = false;
}

if(! $done) {

	require_once 'modules/Accounts/Account.php';

	$tables = array(/*{{{*/
		'i' => array(
			'table' => 'invoice',
			'field' => 'billing_account_id',
			'role' => 'customer',
		),
		'q' => array(
			'table' => 'quotes',
			'field' => 'billing_account_id',
			'role' => 'customer',
		),
		'so' => array(
			'table' => 'sales_orders',
			'field' => 'billing_account_id',
			'role' => 'customer',
		),
		's' => array(
			'table' => 'shipping',
			'field' => 'shipping_account_id',
			'role' => 'customer',
		),
		'ds' => array(
			'table' => 'purchase_orders',
			'field' => 'shipping_account_id',
			'role' => 'customer',
		),
		'pyi' => array(
			'table' => 'payments',
			'field' => 'account_id',
			'role' => 'customer',
			'condition' => 'direction=\'incoming\'',
		),


		'po' => array(
			'table' => 'purchase_orders',
			'field' => 'supplier_id',
			'role' => 'vendor',
		),
		'b' => array(
			'table' => 'bills',
			'field' => 'supplier_id',
			'role' => 'vendor',
		),
		'r' => array(
			'table' => 'receiving',
			'field' => 'supplier_id',
			'role' => 'vendor',
		),
		'pyo' => array(
			'table' => 'payments',
			'field' => 'account_id',
			'role' => 'vendor',
			'condition' => 'direction=\'outgoing\'',
		),
	);/*}}}*/

	$subqueries = array();
	$select = array();

	$sales = array();
	$purchases = array();

	foreach ($tables as $alias => $p) {
		$select[] = "{$alias}_q.{$alias}";
		$condition = '';
		if (isset($p['condition'])) {
			$condition = " AND " . $p['condition'];
		}	
		$subqueries[] = "LEFT JOIN (SELECT {$p['field']} rid, IFNULL(COUNT(id),0) $alias FROM {$p['table']} WHERE (!deleted OR deleted IS NULL) $condition  GROUP BY {$p['field']}) {$alias}_q ON {$alias}_q.rid = accounts.id";
		if ($p['role'] == 'vendor') {
			$purchases[] = $alias;
		} else {
			$sales[] = $alias;
		}

	}
	$sales_expression = "IFNULL(" . join(",0) + IFNULL(", $sales) . ",0)";
	$purchase_expression = "IFNULL(" . join(",0) + IFNULL(", $purchases) . ",0)";

	$query = "SELECT $sales_expression AS num_sales, $purchase_expression AS num_purchases,  accounts.id, " . join(", ", $select) . " FROM accounts " . join(" ", $subqueries);
	$query .= " WHERE $sales_expression > 0";
	$query .= " OR $purchase_expression > 0";

	$res = $db->query($query, true);

	while ($row = $db->fetchByAssoc($res)) {
		$customer = null;
		$vendor = null;


		if (!$row['num_purchases']) { // customer
			$is_supplier = 0;
		} elseif (!$row['num_sales']) { // supplier
			$is_supplier = 1;
		} else {
			$customer = new Account;
			$customer->retrieve($row['id']);
			$customer->is_supplier = 0;

			$vendor = new Account;
			$vendor->retrieve($row['id']);

			$vendor->id = create_guid();
			$vendor->new_with_id = true;
			$vendor->name = '[S] ' . $vendor->name;
			$vendor->is_supplier = 1;

			foreach ($purchases as $alias) {
				if ($row[$alias]) {
					$condition = '';
					if (isset($tables[$alias]['condition'])) {
						$condition = " AND " . $p['condition'];
					}	
					$query = "UPDATE {$tables[$alias]['table']} SET {$tables[$alias]['field']} = '{$vendor->id}' WHERE {$tables[$alias]['field']} = '{$customer->id}' $condition";
					$db->query($query, true);
				}
			}
		}

		if ($customer) {
			$customer->save();
			$customer->cleanup();
			unset($customer);
			$vendor->save();
			$vendor->cleanup();
			unset($vendor);
		} else {
			$query = "UPDATE accounts SET is_supplier = $is_supplier WHERE id='{$row['id']}'";
			$db->query($query, true);
		}

	}
	require_once('modules/Versions/Version.php');
	unset($_SESSION['upgrade_customers_vendors2']);
	Version::mark_upgraded('Customers/Vendors', '6.5.0', '6.5.0');
	echo translate('LBL_UPGRADE_COMPLETE', 'Administration');
} else {
	echo translate('LBL_UPGRADE_PERFORMED', 'Administration');
}


