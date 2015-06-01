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

require_once('modules/Shipping/Shipping.php');
require_once('modules/ProductCatalog/Product.php');
require_once('modules/Shipping/ShippingFormBase.php');
require_once('log4php/LoggerManager.php');
require_once('include/formbase.php');

$local_log =& LoggerManager::getLogger('index');

$focus = new Shipping;
populateFromPost('', $focus);

if (isset($focus->fetched_row) && isset($focus->fetched_row['shipping_stage'])) {
	$old_stage = $focus->fetched_row['shipping_stage'];
} else {
	$old_stage = '';
}

if ($old_stage == 'Shipped') {
	$line_groups = $focus->get_line_groups();
	$focus->extractQuantities($line_groups, $products, $assemblies, +1, true);
} else {
	$products = $assemblies = array();
}
ShippingFormBase::form_update_line_groups($focus);
$new_line_groups =& $focus->get_line_groups();

$outOfStock = null;

if ($focus->shipping_stage == 'Shipped') {
	$focus->extractQuantities($new_line_groups, $products, $assemblies, -1, true);
	foreach ($products as $id => $data) {
		$q = $data['q'];
		if($q) {
			$res = $db->query("SELECT IFNULL(in_stock,0) + $q AS in_stock FROM products_warehouses WHERE product_id='" . PearDatabase::quote($id) . "' AND warehouse_id ='" . PearDatabase::quote($focus->warehouse_id)	. "'");
			$row = $db->fetchByAssoc($res);
			if ($row) {
				$stock = $row['in_stock'];
			} else {
				$stock = $q;
			}
			if ($stock < 0) {
				if (!$outOfStock) $outOfStock = array();
				$outOfStock[$data['name']] =  $stock;
			}
		}
	}
}

$json = getJSONObj();

?>
<script>
	window.inventoryCheck = <?php
	echo $json->encode(array(
		'outOfStock' => $outOfStock,
		'failure' => !empty($outOfStock),
	));
?>
;</script>

