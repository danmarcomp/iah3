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
require_once('include/utils.php');

class Vacation extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id, $modified_by_name;
	var $created_by, $created_by_name;
	var $assigned_user_id, $assigned_user_name;
	var $description;
	var $status;
	var $days;
	var $leave_type;
	var $date_start;
	var $date_end;
	var $time_start;
	var $time_end;


	// Static members
	var $name;
	var $table_name = "vacations";
	var $object_name = "Vacation";
	var $module_dir = "Vacations";
	var $new_schema = true;

	var $additional_column_fields = Array(
		'assigned_user_name',
		'modified_by_name',
		'created_by_name',
	);
	
	function Vacation()
	{
		parent::SugarBean();
	}
	
	
	
	function get_summary_text()
	{
	    $this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		return $this->assigned_user_name . ' : ' . $this->date_start . '(' . $this->days . ')';
	}
	
	function create_export_query($order_by, $where) {
		if (empty($order_by)) {
			$order_by = 'vacations.date_start';
		}
		return $this->_get_default_export_query($order_by, $where);
	}

	function getDefaultListWhereClause()
	{
		return "! vacations.deleted AND (vacations.status IN ('planned', 'approved'))";
	}
	
	function fill_in_additional_list_fields()
	{
	    $this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
	}
	
	function fill_in_additional_detail_fields()
	{
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_by_name = get_assigned_user_name($this->modified_user_id);
	}


	function get_list_view_data(){
		$temp_array = $this->get_list_view_array();
		$temp_array['DAYS'] = format_number($temp_array['DAYS']);
		return $temp_array;
	}
	
	
    
	function parse_additional_headers(&$list_form, $xTemplateSection) {

	}

	function list_view_parse_additional_sections(&$list_form, $xTemplateSection) {
		return $list_form;
	}

	function get_view_closed_where($params)
	{
		return empty($params['value']) ? "(vacations.status IN ('planned', 'approved'))" : '1';
	}
	function get_view_closed_where_advanced($params)
	{
		return  '1';
	}

	function search_by_date_start($param)
	{
		global $timedate;
		$date =  $timedate->to_db_date($param['value'], false);
		$clause = " (vacations.date_end >= '$date') ";
		return $clause;
	}
    
	function search_by_date_end($param)
	{
		global $timedate;
		$date =  $timedate->to_db_date($param['value'], false);
		$clause = " (vacations.date_start <= '$date') ";
		return $clause;
	}
    
    function bean_implements($interface){
	    switch($interface){
			case 'ACL':return true;
		}
		return false;
	}

    static function init_record(RowUpdate &$upd, $input) {
        if (isset($input['leave_type'])) {
            $update = array();
            $update['leave_type'] = $input['leave_type'];

            if ($input['leave_type'] == 'vacation') {
                $update['status'] = 'planned';
            } elseif ($input['leave_type'] == 'sick') {
                $update['status'] = 'days_taken';
            }

            $upd->set($update);
        }
    }
}


