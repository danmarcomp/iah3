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

class SerialNumber extends SugarBean
{
	var $id;
	var $date_entered;
	var $created_by;
	var $date_modified;
	var $modified_user_id;
	var $deleted;
	var $name;
	var $serial_no;
	var $item_name;
	var $notes;
	var $assembly_id;
	var $asset_id;
	
	var $table_name = 'serial_numbers';
	var $object_name = 'SerialNumber';
	var $module_dir = 'SerialNumbers';
	
	var $js_fields = array(
		'id' => 'instance_id',
		'assembly_id',
		'asset_id',
		'serial_no',
		'serial_no',
		'item_name',
		'notes',
		'deleted',
	);
	
	var $new_schema = true;
	
	function SerialNumber() {
		parent::SugarBean();
	}
	
	function get_summary_text()
	{
		return $this->name;
	}

	function create_list_query($order_by, $where)
	{
		return parent::create_list_query($order_by, $where);
	}
	
	function create_export_query(&$order_by, &$where)
	{
		return parent::create_export_query($order_by, $where);
	}
	
	function fill_in_additional_detail_fields()
	{
		return parent::fill_in_additional_detail_fields();
	}
    
	function get_list_view_data()
	{
		return parent::get_list_view_data();
	}

	function js_encode_list($serial_numbers) {
		require_once('include/JSON.php');
		$json = new JSON(JSON_LOOSE_TYPE);
		$list = array();
		foreach($serial_numbers as $instance) {
			$inst = array();
			foreach($this->js_fields as $k => $f) {
				if(is_int($k)) $k = $f;
				$inst[$f] = $instance->$k;
			}
			$list[] = $inst;
		}
		return count($list) == 0 ? '[]' : $json->encode($list);
	}
	
	function update_serials(&$focus, $json_update, $assembly_id='', $asset_id='') {
		require_once('include/JSON.php');
		$json = new JSON(JSON_LOOSE_TYPE);
		$updates = $json->decode($json_update);
		if(is_array($updates)) {
			foreach($updates as $row) {
				$seed = new SerialNumber();
				if(!empty($row['instance_id']))
					$seed->retrieve($row['instance_id']);
				foreach($seed->js_fields as $k => $v) {
					if(is_int($k)) $k = $v;
					if(isset($row[$v]))
						$seed->$k = $row[$v];
				}
				if(empty($seed->assembly_id))
					$seed->assembly_id = $assembly_id;
				if(empty($seed->asset_id))
					$seed->asset_id = $asset_id;
				$seed->save();
				$seed->cleanup();
			}
		}
	}
	
	function &get_list(&$focus, $offset=0, $limit_where='', $components=false) {
		/*if($focus->object_name == 'SupportedAssembly' && !$components)
			$where .= "AND (asset_id IS NULL OR asset_id = '')";*/
		if(empty($focus->id)) {
			$response = array('list' => array(), 'row_count' => 0);
		} else {
			$where = 'NOT deleted';
			if($limit_where)
				$where = empty($where) ? $limit_where : "$where AND $limit_where";
			$response = $focus->get_related_list($this, 'serial_numbers', "", $where, $offset /*, $limit=-1, $max=-1, $show_deleted = 0*/);
		}
		return $response;
	}
}

?>
