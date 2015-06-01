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


require_once('log4php/LoggerManager.php');
require_once('include/database/PearDatabase.php');
require_once('data/SugarBean.php');


class Discount extends SugarBean 
{
	// Stored fields
	var $id;
	var $name;
	var $status;
	var $rate;
	var $deleted;
	var $date_entered;
	var $date_modified;
	var $hide = '';
	var $unhide = '';
	var $discount_type;
	var $fixed_amount;
	var $fixed_amount_usdollar;
	var $currency_id;
	var $exchange_rate;
	var $applies_to_selected;
	

	var $table_name = "discounts";
	var $object_name = "Discount";
	var $module_dir = "Discounts";
	var $new_schema = true;

	var $list_fields ;
	
	
	var $required_fields = array('name'=>1, 'status'=>3);
	

	function Discount() 
	{
		parent::SugarBean();
		$this->list_fields =  array_merge($this->column_fields, array('hide', 'unhide', 'raw_rate', 'raw_type'));
	}

	function getDefaultDiscountName(){
		return translate('LBL_NONE', 'Discounts');	
	}
	
	function process_list_query($query, $row_offset, $limit= -1, $max_per_page = -1, $where = '') {
		$add_blank = empty($row_offset);
		if($add_blank) {
			$blank = new Discount();
			$blank->retrieve('-99');
			
			$results = parent::process_list_query($query, $row_offset, $limit, $max_per_page, $where);
			array_unshift($results['list'], $blank);
			$max_per_page = AppConfig::setting('layout.list.max_entries_per_page');
			if($results['row_count'] >= $max_per_page)
				array_pop($results['list']);
		}
		else {
			$row_offset --;
			$results = parent::process_list_query($query, $row_offset, $limit, $max_per_page, $where);
			$results['previous_offset'] ++;
			$results['next_offset'] ++;
		}

		$results['row_count'] ++;
		
		return $results;
	}
	
	function get_list_view_data() {
		$fields = $this->get_list_view_array();
		if ($this->discount_type == 'percentage') {
			$fields['RATE']	= format_number($this->rate, 2, 2) . ' %';
		} else {
			$conversion_params = array('convert' => false, 'currency_symbol' => true, 'currency_id' => $this->currency_id, 'exchange_rate' => $this->exchange_rate);
			$fields['RATE']	= currency_format_number($this->fixed_amount, $conversion_params);
		}
		if (($this->id == -99 || !$this->ACLAccess('edit')) && empty($this->hide)) {
			$fields['HIDE_EDIT'] = '<!--';
			$fields['UNHIDE_EDIT'] = '-->';
		}
		if (($this->id == -99 || !$this->ACLAccess('delete')) && empty($this->hide)) {
			$fields['HIDE_DELETE'] = '<!--';
			$fields['UNHIDE_DELETE'] = '-->';
		}
		if (!empty($this->hide)) $fields['HIDE'] = $this->hide;
		if (!empty($this->unhide)) $fields['UNHIDE'] = $this->unhide;
		$fields['RAW_RATE']	= $this->rate;
		$fields['RAW_TYPE']	= $this->discount_type;
        return $fields;
	}
	 
	 function list_view_parse_additional_sections(&$list_form)
	{
		global $isMerge;
		
		if(isset($isMerge) && $isMerge && $this->id != '-99'){
		$list_form->assign('PREROW', '<input name="mergecur[]" type="checkbox" value="'.$this->id.'">');
		}
		return $list_form;
	}
	function retrieve($id, $encode = true){
     	if($id == '-99'){
     		$this->name = $this->getDefaultDiscountName();
     		$this->id = '-99';
     		$this->rate = '0.00';
     		$this->deleted = 0;
			$this->status = 'Active';
			$this->discount_type = 'percentage';
     		$this->hide = '<!--';
			$this->unhide = '-->';
     	}else{
     		parent::retrieve($id, $encode);	
     	}
     	if(!isset($this->name) || $this->deleted == 1){
     		$this->name = 	$this->getDefaultDiscountName();
     		$this->id = '-99';
     		$this->rate = '0.00';
     		$this->deleted = 0;
     		$this->status = 'Active';
     		$this->hide = '<!--';
     		$this->unhide = '-->';
			$this->discount_type = 'percentage';
     	}
     	return $this;
     }

	function save($notify = false)
	{
		$this->unformat_all_fields();
		require_once('modules/Currencies/Currency.php');
		$currency  = new Currency();
		$currency->retrieve($this->currency_id);
		adjust_exchange_rate($this, $currency);
		$this->fixed_amount_usdollar = $currency->convertToDollar($this->fixed_amount);
		$currency->cleanup();
		return parent::save($notify);
	}

	function bean_implements($interface){
		switch($interface){
			case 'ACL':return true;
		}
		return false;
	}
	
	function get_summary_text()
	{
		return $this->name;
	}

	function create_list_query($order_by, $where, $show_deleted = 0)
	{
		$add_products = strpos($where, 'discounts_products') !== false;

		$custom_join = $this->custom_fields ? $this->custom_fields->getJOIN() : '';
		$query = "SELECT $this->table_name.* ";
		if($custom_join){
			$query .= $custom_join['select'];
 		}
		$query .= " FROM $this->table_name ";
		if ($add_products)
		$query .=		"LEFT JOIN discounts_products ON discounts_products.discount_id = discounts.id AND discounts_products.deleted = 0 ";

		if($custom_join){
  				$query .= $custom_join['join'];
		}
		$where_auto = '1=1';
		if($show_deleted == 0){
            	$where_auto = " $this->table_name.deleted=0 ";
		}else if($show_deleted == 1){
				$where_auto = " $this->table_name.deleted=1 ";	
		}

		if($where != "")
			$query .= "where ($where) AND ".$where_auto;
		else
			$query .= "where ".$where_auto;
		if(!empty($order_by))
		    $query .=  " ORDER BY ". $this->process_order_by($order_by, null);
		return $query;
	}

}


?>
