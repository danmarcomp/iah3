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

$json_supported_actions['get_attributes_for_product'] = array();

function json_get_attributes_for_product() {
	require_once('modules/ProductCatalog/Product.php');
	require_once('modules/ProductAttributes/ProductAttribute.php');
	
	if(isset($_REQUEST['record'])) {
		$ret = _get_attrs($_REQUEST['record']);
	}
	else if(isset($_REQUEST['record_ids'])) {
		if(is_array($_REQUEST['record_ids']))
			$ids = $_REQUEST['record_ids'];
		else
			$ids = explode(',', $_REQUEST['record_ids']);
		$ids = array_unique($ids);
		$ret = array();
		foreach($ids as $id) {
			if($id)
				$ret = array_merge($ret, _get_attrs($id, true));
		}
	}
	json_return_value($ret);
}

function _get_attrs($record, $set_parent_id=false) {
	static $seed_attr;
	if(! isset($seed_attr)) $seed_attr = new ProductAttribute;
	$prod = new Product;
	if(! $prod->retrieve($record))
		return array();
	if(! $prod->load_relationship('productattributes'))
		return array();
	$ret = array();
	foreach ($prod->productattributes->getBeans($seed_attr) as $attr) {
		$row = $attr->get_JSON_array();
		if($set_parent_id)
			$row['parent_id'] = $record;
		$ret[] = $row;
	}
	$prod->cleanup();
	return $ret;
}



?>
