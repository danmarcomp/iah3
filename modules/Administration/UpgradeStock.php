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

require_once 'modules/ProductCatalog/Product.php';
require_once 'modules/CompanyAddress/CompanyAddress.php';

global $db;


$qDone = "SELECT * FROM versions WHERE name = 'Products stock'";
$rDone = $db->query($qDone);
$rowsDone = $db->getRowCount($rDone);
if($rowsDone > 0) {
	$done = true;
} else {
	$done = false;
}


if(! $done) {
	$confirm = ! empty($_REQUEST['confirm']);
	if($confirm) {
		$seed = new Product;
		$seed->db->query("DELETE FROM products_warehouses");
		$products = $seed->get_list();
		foreach ($products['list'] as $prod) {
			$prod->load_relationship('warehouses');
			$stock = $prod->in_stock ? $prod->in_stock : 0;
			$prod->warehouses->add($_REQUEST['default_warehouse'], array('in_stock' => $stock));
			$prod->all_stock = $stock;
			$prod->save();
		}
		$addr = new CompanyAddress;
		$addr->db->query("UPDATE company_addresses SET main_warehouse = 0");
		$res = $addr->db->query("SELECT COUNT(*) AS c FROM company_addresses WHERE deleted=0 AND is_warehouse=1 AND main_warehouse=1");
		$row = $addr->db->fetchByAssoc($res);
		$addr->retrieve($_REQUEST['default_warehouse']);
		$addr->is_warehouse = 1;
		$addr->main_warehouse = 1;
		$addr->save();
		require_once('modules/Versions/Version.php');
		unset($_SESSION['upgrade_products_stock']);
		Version::mark_upgraded('Products stock', '5.3.0', '5.3.0');
		echo translate('LBL_UPGRADE_STOCK_COMPLETE', 'Administration');
	}
	else {
		$addr = new CompanyAddress;
		$warehouse_options = $addr->get_warehouse_options(null, true);
		echo translate('LBL_UPGRADE_STOCK_CONFIRM', 'Administration');
		$btn_label = htmlentities(translate('LBL_CONFIRM_BUTTON', 'Administration'));
		echo <<<EOF
			<form action="index.php" method="POST">
			<input type="hidden" name="module" value="Administration">
			<input type="hidden" name="action" value="UpgradeStock">
			<input type="hidden" name="confirm" value="true">
			<select name="default_warehouse">$warehouse_options</select>
			<input type="submit" value="$btn_label">
			</form>
EOF;
	}
}
else
	echo translate('LBL_UPGRADE_PERFORMED', 'Administration');

?>
