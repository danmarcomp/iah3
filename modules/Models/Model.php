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
require_once('include/utils.php');

class Model extends SugarBean {	

	// Stored fields
  	var $id;
	var $name;
	var $description;
	var $deleted;

    var $manufacturer_id;
    var $manufacturer_name;
	
	
	var $table_name = 'models';
	var $object_name = 'Model';
	var $module_dir = 'Models';
	var $new_schema = true;


	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
        'manufacturer_name',
	);

	
	function Model() {
		parent::SugarBean();
	}


	function get_summary_text()
	{
		return "$this->name";
	}

	function create_list_query($order_by, $where)
	{
		$query = "
			SELECT 
				`".$this->table_name."`.*,
                accounts.name as manufacturer_name, accounts.assigned_user_id AS manufacturer_name_owner

			FROM `".$this->table_name."`
            LEFT JOIN accounts ON accounts.id=models.manufacturer_id
		";
		
		$where_auto = " not models.deleted ";

		if($where != "")
			$query .= "where $where AND ".$where_auto;
		else
			$query .= "where ".$where_auto;

		if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY `".$this->table_name."`.name";

		return $query;
	}



	function save_relationship_changes( $is_update )
	{
	
	}



	/// This function fills in data for the list view only.
	function fill_in_additional_list_fields()
	{
	}


	/// This function fills in data for the detail view only.
	function fill_in_additional_detail_fields()
	{
		$query = "
			SELECT 
				accounts.name AS manufacturer_name, accounts.assigned_user_id AS manufacturer_name_owner
			FROM accounts
			WHERE '{$this->manufacturer_id}' = accounts.id
			";

		$result = $this->db->query($query, true,"Error filling in additional detail fields: ");

		$row = $this->db->fetchByAssoc($result);
		if($row) {
			$this->manufacturer_name = $row['manufacturer_name'];
			$this->manufacturer_name_owner = $row['manufacturer_name_owner'];
        } else {
			$this->manufacturer_name = '';
			$this->manufacturer_name_owner = '';
        }
	}


	function get_list_view_data()
	{

		$temp_array = $this->get_list_view_array();
		
		return $temp_array;
	}


	/// Called when this object is created or modified.
	///
	function save($check_notify = FALSE) {
	
		return parent::save($check_notify);
	}

	function parse_additional_headers(&$list_form, $xTemplateSection) {

	}

	function list_view_parse_additional_sections(&$list_form, $xTemplateSection) {

		return $list_form;
	}

	function listviewACLHelper() {
		$array_assign = parent::listviewACLHelper();
		$array_assign['MANUFACTURER'] = $this->getACLTagName('manufacturer_name_owner', 'Accounts');
		return $array_assign;
	}

	function bean_implements($interface) {
		switch($interface) {
			case 'ACL':return true;
		}
		return false;
	}

}

?>
