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

class ProductAttribute extends SugarBean {	

	// Stored fields
  	var $id;
	var $name;
	var $deleted;
	var $eshop;
	
	
	var $table_name = 'product_attributes';
	var $object_name = 'ProductAttribute';
	var $module_dir = 'ProductAttributes';
	var $new_schema = true;


	
	function get_JSON_array() {
		static $fields = array('id', 'name', 'value', 'price', 'price_usdollar', 'currency_id', 'exchange_rate');
		$row = array();
		foreach ($fields as $f) {
			$row[$f] = $this->$f;
		}
		return $row;
	}

    static function init_record(RowUpdate &$upd, $input) {
        if (isset($input['return_record'])) {
            $upd->set('product_id', $input['return_record']);
        }
    	if(empty($input['currency_id'])) {
			self::update_currency($upd);
    	}
    }
    
    static function update_currency(RowUpdate $upd) {
        if( ($pid = $upd->getField('product_id')) ) {
        	$result = ListQuery::quick_fetch_row('Product', $pid, array('currency_id', 'exchange_rate'));
        	if($result) {
        		$upd->set('currency_id', $result['currency_id']);
        		$upd->set('exchange_rate', $result['exchange_rate']);
        		if(! $upd->new_record && $upd->getFieldUpdated('exchange_rate')) {
        			if($result['exchange_rate']) {
						$upd->set('price', $upd->getField('price_usdollar', 0) * $result['exchange_rate']);
					}
        		}
        	}
        }
    }
}
?>