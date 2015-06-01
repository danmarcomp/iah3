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


require_once 'data/SugarBean.php';

class Partner extends SugarBean
{

	var $id;
	var $name;
	var $code;
	var $date_start;
	var $date_end;
	var $lead_exclusivity;
	var $lead_revenue_sharing;
	var $commission_rate;
	var $description;
	var $deleted;
	var $created_by, $modified_user_id;
	var $assigned_user_id;
	var $assigned_user_name;
	var $created_by_name, $modified_by_name;
	var $related_account_name;


	var $table_name = 'partners';
	var $object_name = 'Partner';
	var $module_dir = 'Partners';
	var $new_schema = true;
	
	var $additional_column_fields = Array(
		'assigned_user_name',
		'modified_by_name',
		'created_by_name',
		'related_account_name',
	);

	function get_summary_text()
	{
		return "" . $this->name;
	}

	function fill_in_additional_detail_fields()
	{
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_by_name = get_assigned_user_name($this->modified_user_id);
		$this->fill_account_name();
	}

	function get_list_view_data()
	{
		$this->fill_account_name();
		$ret = $this->get_list_view_array();
		return $ret;
	}
	
	function fill_account_name()
	{
		$this->related_account_name = '';
		if(! empty($this->related_account_id)) {
			$q = 'SELECT name FROM accounts WHERE id="'.$this->db->quote($this->related_account_id).'"';
			$r = $this->db->query($q, true);
			if($row = $this->db->fetchByAssoc($r))
				$this->related_account_name = $row['name'];
		}
	}
	
	function create_export_query(&$order_by, &$where) {
    	return parent::_get_default_export_query($order_by, $where);
	}

	function bean_implements($interface)
	{
		switch($interface) {
			case 'ACL':return true;
		}
		return false;
	}
	
}

