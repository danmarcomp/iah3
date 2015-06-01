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

class BookingCategory extends SugarBean {
	
	// Standard stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $created_by;
	var $modified_user_id;
	
	// Relevant stored fields
	var $name;
	var $booking_class;
	var $status;
	var $tax_code_id;

	var $location;
	var $seniority;
	var $duration;
	var $expenses_unit;

	var $billing_rate;
	var $billing_rate_usd;
	var $billing_currency_id;
	var $billing_exchange_rate;
	var $paid_rate;
	var $paid_rate_usd;
	var $paid_currency_id;
	var $paid_exchange_rate;

	// Not stored
	var $created_by_name;
	var $modified_user_name;
	var $tax_code_name;
	
    var $object_name = 'BookingCategory';
	var $module_dir = 'BookingCategories';
	var $new_schema = true;
	var $table_name = 'booking_categories';

	// This is used to populate additional fields on the DetailView
	var $additional_column_fields = Array(
		'created_by_name',
		'modified_user_name',
		'tax_code_name',
	);

	function BookingCategory() {
		parent::SugarBean();
	}


	function get_display_unit($spec, $table, $context) {
		if($context == 'order_by') {
			// return sql expression
			global $db;
			$tbl = $db->quoteField($table);
			return "CASE WHEN $tbl.booking_class='expenses' THEN $tbl.expenses_unit ELSE $tbl.duration END";
		}
		else if($context == 'display') {
			if($spec['raw_values']['booking_class'] == 'expenses')
				return $spec['values']['expenses_unit'];
			return $spec['values']['duration'];
		}
	}
	
	static function get_work_options($as_array=true) {
		$lq = new ListQuery('BookingCategory', true);
		$lq->addDisplayName();
		$lq->addSimpleFilter('booking_class', array('billable-work', 'nonbill-work'));
		$ret = $lq->fetchAll('name');
		if($as_array) return $ret->rows;
		return $ret;
	}
	
	function get_option_list($cls='', $add_blank=false, $group=false) {
		if($cls == 'work')
			$cls = array('billable-work', 'nonbill-work');
		else if($cls == 'services')
			$cls = array('services-monthly');
		else if($cls == 'expenses')
			$cls = array('expenses');
		if(! is_array($cls)) $cls = array($cls);
		
		if($cls[0]) {
			$cls = "('".implode("','", $cls)."')";
			$w = "t.booking_class IN $cls";
		}
		else $w = '1';
		
		$q = "SELECT t.id, t.name, t.booking_class FROM {$this->table_name} t WHERE $w AND NOT t.deleted ORDER BY t.name";
		$r = $this->db->query($q, true, "Error retrieving booking categories");
		$ret = array();
		
		if($add_blank)
			$ret[''] = $GLOBALS['app_strings']['LBL_NONE'];
		
		$this->all_billable = array();
		while($row = $this->db->fetchByAssoc($r)) {
			if($group) {
				$c = $row['booking_class'];
				$cls_name = array_get_default($app_list_strings['booking_classes_dom'], $c, $c);
				$ret[$cls_name][$row['id']] = $row['name'];
				if($c == 'billable-work')
					$this->all_billable[$row['id']] = 1;
			} else
				$ret[$row['id']] = $row['name'];
		}
		
		if($group)
			ksort($ret);
		
		return $ret;
	}
	
	
	function save($check_notify = FALSE) {
		require_once('modules/Currencies/Currency.php');
		
		// this must be done before interpreting numeric values
		$this->unformat_all_fields();

		$bill_currency = new Currency();
		$bill_currency->retrieve($this->billing_currency_id);
		$params = array('currency_field' => 'billing_currency_id', 'rate_field' => 'billing_exchange_rate');
		$rate_changed = adjust_exchange_rate($this, $bill_currency, $params);
		$this->billing_rate_usd = $bill_currency->convertToDollar($this->billing_rate);

		if ($this->booking_class == 'expenses') {
			$this->currency_id = -99;
			$this->exchange_rate = 1.0;
		}
		$paid_currency = new Currency();
		$paid_currency->retrieve($this->paid_currency_id);
		$params = array('currency_field' => 'paid_currency_id', 'rate_field' => 'paid_exchange_rate');
		$rate_changed = adjust_exchange_rate($this, $paid_currency, $params);
		$this->paid_rate_usd = $paid_currency->convertToDollar($this->paid_rate);
		
		return parent::save($check_notify);
	}

    static function format_display_name($name, $cls) {
    	global $app_list_strings;
    	if($cls) {
			$tcls = array_get_default($app_list_strings['booking_classes_dom'], $cls, $cls);
			$name .= " ($tcls)";
		}
		return $name;
    }
}


?>
