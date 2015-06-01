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


require_once('data/SugarBean.php');

class ShippingLine extends SugarBean {

	var $module_dir = 'Shipping';
	var $object_name = 'ShippingLine';
	var $table_name = 'shipping_lines';
	
	var $currency_fields = array(
		'unit_price' => 'unit_price_usd',
		'std_unit_price' => 'std_unit_price_usd',
		'ext_price' => 'ext_price_usd',
	);
	
	// static methods

	function pre_insert_row(&$group, &$row) {
		return $this->pre_update_row($group, $row);
	}
	
	function pre_update_row(&$group, &$row) {
		if(empty($row['line_group_id']))
			return false; // lines must belong to a line group
		foreach(array_keys($row) as $k)
			if(! in_array($k, $this->column_fields))
				unset($row[$k]);
		$currency =& $group->get_currency();
		foreach($this->currency_fields as $f => $f_u) {
			$raw = isset($row[$f]) ? $row[$f] : '';
			$row[$f_u] = $currency->convertToDollar($raw);
		}
		return true;
	}
}

?>
